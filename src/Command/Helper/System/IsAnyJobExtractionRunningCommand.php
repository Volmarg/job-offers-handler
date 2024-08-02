<?php

namespace JobSearcher\Command\Helper\System;

use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Will check if there is any job extraction currently running.
 * That's especially helpfully for live system updates where there might be some processes running and breaking them
 * could result in necessity of re-founding points etc.
 */
class IsAnyJobExtractionRunningCommand extends AbstractCommand
{
    private const COMMAND_NAME = "helper:system:is-any-job-extraction-running";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface        $logger
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setDescription("Will check if any job offer extraction is running");
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

        try {
            $isRunning = $this->offerExtractionService->isAnyExtractionRunning();
            if (!$isRunning) {
                $this->io->success("No extraction is running right now.");
                return self::SUCCESS;
            }

            $this->io->warning("At least one extraction is running now!");
        } catch (Exception|TypeError $e) {
            $this->logger->critical("Exception was thrown while calling command", [
                "class" => self::class,
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}
