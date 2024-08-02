<?php

namespace JobSearcher\Command\Monitoring\System;

use DateTime;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Will search for any long-running job searches / extractions
 * This was added due to the job searches sometimes freezing, it's unknown why,
 * But in case it happens the command will report any extractions running more than
 * - {@see LongRunningJobExtractionCommand::EXTRACTION_MIN_RUN_TIME}
 */
class LongRunningJobExtractionCommand extends AbstractCommand
{
    private const COMMAND_NAME = "monitoring:system:find-long-running-job-extraction";

    private const EXTRACTION_MIN_RUN_TIME = 60; // minutes

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    protected function configure(): void
    {
        $this->setDescription("Find any long running job extractions, print them out + send critical");
        parent::configure();
    }

    /**
     * @param LoggerInterface              $logger
     * @param JobOfferExtractionRepository $jobOfferExtractionRepository
     */
    public function __construct(
        LoggerInterface                               $logger,
        private readonly JobOfferExtractionRepository $jobOfferExtractionRepository
    )
    {
        parent::__construct($logger);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now            = new DateTime();
        $jobExtractions = $this->jobOfferExtractionRepository->findRunningLongerThan(self::EXTRACTION_MIN_RUN_TIME);
        $tableHeaders   = [
            "id", "pagination pages", "running time (h)", "status"
        ];

        $tableRows  = [];
        $loggerData = [];
        foreach ($jobExtractions as $extraction) {
            $minutes = $extraction->getCreated()->diff($now)->i;
            $hours   = round($minutes / 60, 2);

            $tableRows[] = [
              $extraction->getId(),
              $extraction->getPaginationPagesCount(),
              $hours,
              $extraction->getStatus(),
            ];

            $loggerData[] = [
                "id: {$extraction->getId()}",
                "pagesCount: {$extraction->getPaginationPagesCount()}",
                "status: {$extraction->getStatus()}",
                "hours: {$hours}",
            ];
        }

        $this->logger->critical("There are some job extractions running longer than: " . self::EXTRACTION_MIN_RUN_TIME . " minutes", [
            $loggerData,
        ]);

        $this->io->table($tableHeaders, $tableRows);

        return self::SUCCESS;
    }

}