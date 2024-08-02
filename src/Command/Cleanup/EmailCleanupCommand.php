<?php

namespace JobSearcher\Command\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Entity\Email\Email2Company;
use JobSearcher\Service\Email\EmailService;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Log\LoggerInterface;
use SmtpEmailValidatorBundle\Service\SmtpValidator;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use TypeError;

/**
 * Will check if the E-Mails in DB are still valid,
 * If E-Mail turns out to be invalid then it will:
 * - be removed from DB
 * - be detached from the {@see Email2Company},
 * - if the E-Mail is related to the job-offer, then it will get detached from it as well
 */
class EmailCleanupCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "cleanup:email";

    /**
     * The more emails are checked the longer it takes,
     * on old laptop with low internet connection it was:
     * - 250 entries => ~10 minutes
     *
     * On rather fine desktop pc
     * - 250 entries => ~1.5 min
     */
    private const MAX_EMAILS_COUNT_PER_RUN = 1000;

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface        $logger
     * @param SmtpValidator          $smtpValidator
     * @param EntityManagerInterface $em
     * @param EmailService           $emailService
     * @param ParameterBagInterface  $parameterBag
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private readonly SmtpValidator          $smtpValidator,
        private readonly EntityManagerInterface $em,
        private readonly EmailService           $emailService,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Will check if there are any invalid E-Mails to be cleaned");
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->offerExtractionService->isAnyExtractionRunning()) {
            $this->io->warning(self::MSG_EXTRACTION_IS_RUNNING);
            return self::SUCCESS;
        }

        if (!$this->lock(self::COMMAND_NAME)){
            $output->writeln("This command is already running");
            return self::SUCCESS;
        }

        try {
            $this->io->info("Started cleaning emails");

            $emailRepository = $this->em->getRepository(Email::class);
            $emailsToCheck   = $emailRepository->getForValidation(
                self::MAX_EMAILS_COUNT_PER_RUN,
                (int)$this->parameterBag->get('email_re_validated_after_days')
            );

            $allEmailAddresses = array_map(
                fn(Email $email) => $email->getAddress(),
                $emailsToCheck,
            );

            $this->io->info("Checking " . count($allEmailAddresses) . " E-mail/s");

            $validationResults = $this->smtpValidator->validateEmail($allEmailAddresses);
            $invalidEntities   = [];

            foreach ($emailsToCheck as $emailEntity) {
                $isEmailValid = ($validationResults[$emailEntity->getAddress()] ?? false);
                if (!$isEmailValid) {
                    $invalidEntities[] = $emailEntity;
                }
            }

            $this->emailService->setValidationDate($emailsToCheck);
            $emailRepository->removeEmails($invalidEntities);

            $this->io->info("Invalid E-Mails count " . count($invalidEntities));

            $this->io->info("Finished cleaning emails");
            $this->release();
        }catch(Exception | TypeError $e){
            $this->logger->critical("Exception was thrown while calling command", [
                "class" => self::class,
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);

            $this->release();
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}