<?php

namespace JobSearcher\Command\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use JobSearcher\Command\AbstractCommand;
use Exception;
use JobSearcher\Repository\Company\CompanyRepository;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use TypeError;

/**
 * Handles removing the old companies. Removes all the data related to company:
 * - location,
 * - branch,
 * - emails,
 */
class CompanyCleanupCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "cleanup:companies";

    /**
     * Reducing the removal per run to reduce the time necessary for the cleanup command to be done
     */
    private const MAX_COMPANIES_REMOVED_PER_RUN = 1000;

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    private readonly int $maxDaysLifetimeCompanyWithoutOffers;

    /**
     * @param LoggerInterface        $logger
     * @param CompanyRepository      $companyRepository
     * @param ParameterBagInterface  $parameterBag
     * @param EntityManagerInterface $entityManager
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private readonly CompanyRepository      $companyRepository,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        $this->maxDaysLifetimeCompanyWithoutOffers = (int)$this->parameterBag->get("max_days_unrelated_company_lifetime");
        $this->logger                              = $logger;
        parent::__construct($this->logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Handles removal of extractions and related job offers");
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
            $this->entityManager->beginTransaction();

            $this->io->info("Started removing companies that were not related to offers for: {$this->maxDaysLifetimeCompanyWithoutOffers} days");

            $unrelatedCompanies      = $this->companyRepository->findAllWithoutOffersRelatedForDays($this->maxDaysLifetimeCompanyWithoutOffers, self::MAX_COMPANIES_REMOVED_PER_RUN);
            $countUnrelatedCompanies = count($unrelatedCompanies);

            $this->io->info("Found {$countUnrelatedCompanies} company/ies to remove");

            // will wipe also the related data (check the entity relations and handling of cascade removal to see what gets removed)
            foreach ($unrelatedCompanies as $company) {
                if (!$company->canRemove()) {
                    $this->logger->warning("Cannot remove company with id: {$company->getId()}");
                    continue;
                }
                $this->entityManager->remove($company);
            }

            $this->entityManager->flush();

            $removalCount = count($unrelatedCompanies);
            $this->io->note("Finished removing companies with related data. Removed companies count: {$removalCount}");

            $this->entityManager->commit();
            $this->release();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
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
