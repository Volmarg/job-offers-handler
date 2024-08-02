<?php

namespace JobSearcher\Command\Cleanup;

use DateTime;
use JobSearcher\Command\AbstractCommand;
use Exception;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use TypeError;

/**
 * Handles removing the old offers
 */
class DebugDataCleanupCommand extends AbstractCommand
{
    use LockableTrait;

    private const REMOVAL_REASON_NO_MTIME_STAMP = "FileHasNoModificationStamp";
    private const REMOVAL_REASON_EXPIRED        = "FileMaxLifetimeExceeded";

    public const COMMAND_NAME = "cleanup:debug-data";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface        $logger
     * @param string                 $debugDataMaxDaysLifetime
     * @param array                  $cleanedDirectories
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private readonly string                 $debugDataMaxDaysLifetime,
        private readonly array                  $cleanedDirectories,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Handles removal of old debug data");
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
            $this->io->info("Started removing debug data");

            $now              = new DateTime();
            $finder           = new Finder();
            $removedFilePaths = [];
            foreach ($finder->in($this->cleanedDirectories)->files() as $file) {

                // using last modified stamp because there is no `created` stamp, also theoretically these files should not get modified
                $createdTimestamp = $file->getMTime();
                if (is_bool($createdTimestamp)) {
                    unlink($file->getPathname());
                    $removedFilePaths[] = $file->getPathname() . " => " . self::REMOVAL_REASON_NO_MTIME_STAMP;
                    continue;
                }

                $lastLifetimeDate = (new DateTime())->setTimestamp($createdTimestamp)->modify("+{$this->debugDataMaxDaysLifetime} DAYS");
                if ($now->getTimestamp() >= $lastLifetimeDate->getTimestamp()) {
                    unlink($file->getPathname());
                    $removedFilePaths[] = $file->getPathname() . " => " . self::REMOVAL_REASON_EXPIRED;
                }

            }

            if (!empty($removedFilePaths)) {
                $this->io->note("Removed files list");
                $this->io->listing($removedFilePaths);
            } else {
                $this->io->note("No debug data was removed");
            }

            $this->io->note("Finished removing debug data");
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

}