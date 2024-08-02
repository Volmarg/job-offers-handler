<?php

namespace JobSearcher\Command\Corrections\Keywords;

use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use JobSearcher\Service\Keywords\KeywordsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Handles fetching keywords for examples for offers that got no keywords yet setc
 */
class KeywordsCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "correction:fetch-keywords";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface        $logger
     * @param KeywordsService        $keywordsService
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private KeywordsService                 $keywordsService,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Will handle fetching keywords for strings depending how the command logic was defined. Afterwards it inserts the keywords in DB");
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

        try{
            $this->io->info("Started fetching keywords");

            $this->keywordsService->getForOffersWithoutKeywords();

            $this->io->info("Finished fetching keywords");
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