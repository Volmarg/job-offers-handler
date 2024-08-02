<?php

namespace JobSearcher\Command\Corrections\System;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Command\Monitoring\System\LongRunningJobExtractionCommand;
use JobSearcher\Constants\RabbitMq\Consumer\JobSearch\DoJobSearchConsumerConstants;
use JobSearcher\Entity\Extraction\Extraction2AmqpRequest;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\Storage\AmqpStorage;
use JobSearcher\RabbitMq\Producer\JobSearch\JobSearchDoneProducer;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use JobSearcher\Service\RabbitMq\JobSearcher\JobSearchDoneProducerService;
use JobSearcher\Service\Shell\Command\ShellCommandService;
use LogicException;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * This command and logic around it was created because it can happen that container will go down or dunno, for
 * example some power outtake etc. It would then cause the {@see JobOfferExtraction::STATUS_IN_PROGRESS} never
 * to be finished. Ofc. there is other command {@see LongRunningJobExtractionCommand} which detects stuck extractions
 *
 * This command here however will look if the extraction in status {@see JobOfferExtraction::STATUS_IN_PROGRESS} is
 * running physically and if not then will mark it as {@see JobOfferExtraction::STATUS_FAILED} and will then emit
 * {@see JobSearchDoneProducer} message to rabbit so that the entry on backend of other project could get updated.
 *
 * The easiest (dream way) would be {@see pcntl_signal} but it doesn't work for SIGKILL etc.
 * which kinda makes sense because how is it supposed to do something is server goes down etc.?
 *
 * This logic in here is valid ONLY for searches executed via GUI, direct CLI invoked calls won't be handled properly or at all
 */
class InProgressNotRunningJobExtractionCommand extends AbstractCommand
{
    use LockableTrait;

    private const COMMAND_NAME = "correction:job-extraction-in-progress-not-running";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface              $logger
     * @param JobOfferExtractionRepository $jobOfferExtractionRepository
     * @param EntityManagerInterface       $entityManager
     * @param JobSearchDoneProducerService $jobSearchDoneProducerService
     * @param ShellCommandService          $commandService
     */
    public function __construct(
        LoggerInterface $logger,
        private readonly JobOfferExtractionRepository $jobOfferExtractionRepository,
        private readonly EntityManagerInterface       $entityManager,
        private readonly JobSearchDoneProducerService $jobSearchDoneProducerService,
        private readonly ShellCommandService          $commandService
    )
    {
        parent::__construct($logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $msg = "Checks if any extraction in status: "
               . JobOfferExtraction::STATUS_IN_PROGRESS
               . " exists without corresponding process, and if is not then will handle the status changing etc.";

        $this->setDescription($msg);
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock(self::COMMAND_NAME)){
            $output->writeln("This command is already running");
            return self::SUCCESS;
        }

        try {
            $this->io->info("Started searching for orphaned extractions");

            $entities = $this->jobOfferExtractionRepository->findAllWithAmqpByStatus(JobOfferExtraction::STATUS_IN_PROGRESS);
            if (empty($entities)) {
                $this->io->note("There are no extractions with status: " . JobOfferExtraction::STATUS_IN_PROGRESS);
                $this->release();
                return self::SUCCESS;
            }

            foreach ($entities as $entity) {

                try {
                    $this->validateExtraction($entity);
                    if (!$this->hasRunningProcess($entity)) {
                        $this->io->note("Got detached extraction entity with id: {$entity->getId()}");
                        $this->handleWithoutProcess($entity);
                    }
                } catch (Exception|TypeError $e) {
                    $this->logger->critical("Issue with one of the entities in command: " . self::COMMAND_NAME, [
                        "info"   => "Skipping this one and going with next one",
                        "entity" => [
                            "class" => JobOfferExtraction::class,
                            "id"    => $entity->getId(),
                        ],
                        "exception" => [
                            "class"   => $e::class,
                            "message" => $e->getMessage(),
                            "trace"   => $e->getTraceAsString(),
                        ]
                    ]);
                    continue;
                }

            }

            $this->io->info("Finished searching for orphaned extractions");
            $this->release();
        } catch (Exception|TypeError $e) {
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

    /**
     * Check if given job offer extraction got corresponding running process
     * (meaning that there is an ongoing extraction process for given entity).
     *
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @return bool
     */
    private function hasRunningProcess(JobOfferExtraction $jobOfferExtraction): bool
    {
        $storageId = $jobOfferExtraction->getExtraction2AmqpRequest()->getAmqpRequest()->getId();

        // need to exclude grep itself else it will find the process running (which will invalidly point to this check command)
        $cliCommand = "ps aux | grep 'bin/console' | grep '{$storageId}' | grep 'php' | grep -v 'grep'";

        // setting logger level to info, because this causes error code if nothing is running, else will get tones of false critical emails
        return $this->commandService->executeWithLoggedOutput($cliCommand, null, Logger::INFO);
    }

    /**
     * Handle the {@see JobOfferExtraction} without running process:
     * - set status to {@see JobOfferExtraction::STATUS_FAILED},
     * - produce rabbit message so that the project which dispatched the search can get the status updated etc.,
     *
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @return void
     */
    private function handleWithoutProcess(JobOfferExtraction $jobOfferExtraction): void
    {
        $jobOfferExtraction->setStatus(JobOfferExtraction::STATUS_FAILED);
        $this->entityManager->persist($jobOfferExtraction);
        $this->entityManager->flush();

        $extraction2Amqp = $jobOfferExtraction->getExtraction2AmqpRequest();
        $dataArray       = json_decode($extraction2Amqp->getAmqpRequest()->getMessage(), true);
        $searchId        = $dataArray[DoJobSearchConsumerConstants::KEY_SEARCH_ID] ?? null;

        if (empty($searchId)) {
            throw new LogicException("Search id is missing in incoming message");
        }

        $this->jobSearchDoneProducerService->produce(
            $extraction2Amqp->getAmqpRequest()->getUniqueId(),
            $jobOfferExtraction->getId(),
            $searchId,
            true,
            JobOfferExtraction::STATUS_FAILED,
            $jobOfferExtraction->getPercentageDone()
        );
    }

    /**
     * Check if the {@see JobOfferExtraction} is valid for this process
     *
     * @param JobOfferExtraction $jobOfferExtraction
     */
    private function validateExtraction(JobOfferExtraction $jobOfferExtraction): void
    {
        $extraction2Amqp = $jobOfferExtraction->getExtraction2AmqpRequest();
        if (empty($extraction2Amqp)) {
            throw new LogicException("No AMQP storage entry for entity: {$jobOfferExtraction->getId()} exists");
        }

        if (empty($extraction2Amqp->getAmqpRequest())) {
            throw new LogicException(Extraction2AmqpRequest::class . " is missing relation to: " . AmqpStorage::class);
        }
    }
}
