<?php

namespace JobSearcher\Command\Cleanup;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use JobSearcher\Command\AbstractCommand;
use Exception;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Service\Cleanup\Duplicate\DuplicateCleanupInterface;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * In general there was/is an issue with parallel calls, that's why some entities such as {@see CompanyBranch}
 * are no longer unique on DB lvl. If there are 2 requests trying to save data on same moment then it was causing
 * {@see UniqueConstraintViolationException}, and there was no easy way to solve that.
 *
 * All the ideas around that were based on DB lock, reducing amount of calls for offers etc. went with idea that:
 * - duplicates are allowed,
 * - once a day - at night, the cleanup will handle the duplicates removal & merging,
 * - even if some data will not be merged (as it might be that it will not be possible to merge it),
 *   then there are still plenty of rules in the project which will eventually remove the offers / companies anyway
 */
class DuplicatesCleanupCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "cleanup:duplicates";

    private const PARAM_EXTRACTION_ID = "extraction-id";

    private const CLEANUP_MAX_DAYS_OFFSET = 5;

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface             $logger
     * @param DuplicateCleanupInterface[] $duplicateCleanupServices
     * @param DuplicateCleanupInterface[] $duplicateRemovalServicesOrdered
     * @param EntityManagerInterface      $entityManager
     * @param OfferExtractionService      $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private readonly array                  $duplicateCleanupServices,
        private readonly array                  $duplicateRemovalServicesOrdered,
        private readonly EntityManagerInterface $entityManager,
        private readonly OfferExtractionService $offerExtractionService
    ) {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Handles merging & eventually removing the duplicated data");
        $this->addOption(
            self::PARAM_EXTRACTION_ID,
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            "Merging will be limited to this extraction. Keep in mind that this was mostly designed for debugging purposes. Is not recommended to use that option in prod",
            []);
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
            $extractionIds = $input->getOption(self::PARAM_EXTRACTION_ID);
            if (!empty($extractionIds)) {
                $this->io->info("Provided extraction ids");
                $this->io->listing($extractionIds);
            }

            if (!$this->io->confirm("Do you want to continue")) {
                $this->io->error("Aborted!");
                $this->release();
                return self::FAILURE;
            }

            $this->io->info("Started duplicates cleanup");
            foreach ($this->duplicateCleanupServices as $cleanupService) {
                $this->io->info("Started calling cleaner: " . $cleanupService::class);
                try {
                    /**
                     * Must clear the manager before each service, else if one removes entities, other tries to fetch
                     * them from {@see UnitOfWork} because these are still cached but are no longe present in DB
                     */
                    $this->entityManager->clear();
                    $cleaningCount = $cleanupService->clean(self::CLEANUP_MAX_DAYS_OFFSET, $extractionIds);
                } catch (Exception|TypeError $e) {
                    $this->logger->critical("Got exception while cleaning duplicates in: " . $cleanupService::class, [
                        "exception" => [
                            "msg"   => $e->getMessage(),
                            "trace" => $e->getTraceAsString(),
                            "class" => $e::class,
                        ],
                    ]);
                    continue;
                }
                $this->io->note("Cleaned: {$cleaningCount} entry/ies");
                $this->io->info("Finished calling cleaner: " . $cleanupService::class);
            }

            $this->removeDuplicates();
            $this->io->note("Finished duplicates cleanup");
            $this->release();
        } catch (Exception|TypeError $e) {
            $this->logger->critical("Exception was thrown while calling command", [
                "class"     => self::class,
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ],
            ]);

            $this->release();
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Handles removing duplicates after handling all the merging
     *
     * This MUST be called in here, because the order of removal must actually be different from the merging,
     * that's due to relations between entities.
     */
    private function removeDuplicates(): void
    {
        foreach ($this->duplicateRemovalServicesOrdered as $service) {
            foreach ($service::getRemovedDuplicates() as $entity) {

                /**
                 * Need to re-fetch due to the earlier usages of {@see EntityManagerInterface::clear()}
                 */
                $removedEntity = $this->entityManager->find($entity::class, $entity->getId());
                if (!empty($removedEntity)) {
                    $this->entityManager->remove($removedEntity);
                }
            }
        }

        $this->entityManager->flush();
    }

}
