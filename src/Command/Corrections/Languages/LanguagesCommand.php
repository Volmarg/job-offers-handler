<?php

namespace JobSearcher\Command\Corrections\Languages;

use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use JobSearcher\Service\Languages\LanguageDetectionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Handles fetching languages for example for offers that got no languages detected yet
 */
class LanguagesCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "correction:fetch-languages";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface          $logger
     * @param LanguageDetectionService $languageDetectionService
     * @param OfferExtractionService   $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private LanguageDetectionService        $languageDetectionService,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Will handle fetching languages for strings depending how the command logic was defined. Afterwards it inserts the languages in DB");
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
            $this->io->info("Started fetching languages");
            {
                $this->languageDetectionService->getForOffersWithoutLanguages();
            }
            $this->io->info("Finished fetching languages");
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