<?php

namespace JobSearcher\Command\Corrections\Location;

use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use JobSearcher\Service\Location\LocationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Handles fetching location for example for companies that have no country set yet
 */
class LocationCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "correction:fetch-location";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LocationService        $locationService
     * @param LoggerInterface        $logger
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        private LocationService                 $locationService,
        LoggerInterface                         $logger,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Will handle fetching location data.");
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
            $this->io->info("Started fetching location data");

            $this->locationService->getForCompaniesWithoutCountry();

            $this->io->info("Finished fetching location data");
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