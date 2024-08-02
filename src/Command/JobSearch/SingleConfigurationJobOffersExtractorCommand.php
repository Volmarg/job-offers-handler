<?php

namespace JobSearcher\Command\JobSearch;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JobSearcher\Exception\Extraction\TerminateProcessException;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\Extraction\Offer\ExtractionProgressDeciderService;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorServiceFactory;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;
use Exception;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use JobSearcher\Service\Symfony\LoggerService;
use JobSearcher\Service\TypeProcessor\DateTimeProcessor;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Will handle extracting job offers for single configuration and source
 */
class SingleConfigurationJobOffersExtractorCommand extends AbstractJobSearchCommand
{
    private const OPTION_LONG_SOURCE  = "source";
    private const OPTION_SHORT_SOURCE = "src";

    private const OPTION_LONG_CONFIGURATION  = "configuration";
    private const OPTION_SHORT_CONFIGURATION = "config";

    private const COMMAND_NAME = "single-configuration-" . self::COMMON_COMMAND_NAME_PART;

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @var ExtractorServiceFactory $extractorServiceFactory
     */
    private ExtractorServiceFactory $extractorServiceFactory;

    /**`
     * @param LoggerInterface                  $logger
     * @param ExtractorServiceFactory          $extractorServiceFactory
     * @param ExtractionProgressDeciderService $extractionProgressDeciderService
     * @param OfferExtractionService           $offerExtractionService
     * @param ConfigurationReader              $configurationReader
     * @param LoggerService                    $loggerService
     */
    public function __construct(
        LoggerInterface                         $logger,
        ExtractorServiceFactory                 $extractorServiceFactory,
        ExtractionProgressDeciderService        $extractionProgressDeciderService,
        private readonly OfferExtractionService $offerExtractionService,
        private readonly ConfigurationReader    $configurationReader,
        private readonly LoggerService          $loggerService
    ) {
        parent::__construct($logger, $configurationReader, $extractionProgressDeciderService);
        $this->extractorServiceFactory = $extractorServiceFactory;
    }

    /**
     * Will return all available sources returned as pretty formatted json string
     *
     * @return string
     * @throws Exception
     */
    private function getAvailableSourcesAsPrettyJson(): string
    {
        $configurationsForSources = [];
        foreach (ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES as $extractionSource) {
            $configurationsForSources[$extractionSource] = $this->configurationReader->getConfigurationNamesForTypeAndCountry(null, $extractionSource);
        }

        $configurationNameFromInputsForSource = json_encode($configurationsForSources, JSON_PRETTY_PRINT);
        return $configurationNameFromInputsForSource;
    }

    /**
     * Will return all available sources returned as pretty formatted json string
     * {@see ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES}
     *
     * @return string
     */
    private function getAllConfigurationsNamesForSourcesAsPrettyJson(): string
    {
        $availableSources = json_encode(ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES, JSON_PRETTY_PRINT);

        return $availableSources;
    }

    /**
     * @throws Exception
     */
    protected function configure(): void
    {
        $this->setDescription("Will extract job offers for single configuration")
            ->addOption(self::OPTION_LONG_SOURCE, self::OPTION_SHORT_SOURCE, InputOption::VALUE_REQUIRED, "Source for which the configuration will be used on ({$this->getAvailableSourcesAsPrettyJson()})")
            ->addOption(self::OPTION_LONG_CONFIGURATION, self::OPTION_SHORT_CONFIGURATION, InputOption::VALUE_REQUIRED, "Configuration for each source ({$this->getAllConfigurationsNamesForSourcesAsPrettyJson()}")
            ->addUsage("")
        ;
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (EnvReader::isDemo()) {
            return self::FAILURE;
        }

        $source                     = $input->getOption(self::OPTION_LONG_SOURCE);
        $configurationNameFromInput = $input->getOption(self::OPTION_LONG_CONFIGURATION);
        $jobOfferExtraction         = $this->createExtractionEntity($source, $configurationNameFromInput);
        if (empty($jobOfferExtraction)) {
            return self::FAILURE;
        }

        try {
            $this->io->info(DateTimeProcessor::nowAsStringWrappedBetweenCharacters("[", "]") . "Started extracting job offers for given configuration");
            $this->listUsedParameters($source, $configurationNameFromInput);
            $this->handleExtraction($source, $configurationNameFromInput, $jobOfferExtraction);

            $this->decideExtractionProgress($jobOfferExtraction, null, [], [$configurationNameFromInput]);
            $this->offerExtractionService->updateExtraction($jobOfferExtraction);
        } catch (TerminateProcessException $tpe) {
            $this->loggerService->logException($tpe);
            $this->decideExtractionProgress($jobOfferExtraction, null, [], [$configurationNameFromInput]);
            $this->offerExtractionService->captureClosingManager($tpe, $jobOfferExtraction);
            return self::FAILURE;
        } catch (Exception|TypeError $e) {
            $this->loggerService->logException($e);
            $this->decideExtractionProgress($jobOfferExtraction, null, [], [$configurationNameFromInput]);
            $this->offerExtractionService->updateExtraction($jobOfferExtraction, JobOfferExtraction::STATUS_FAILED);

            return self::FAILURE;
        }

        $this->offerExtractionService->updateExtraction($jobOfferExtraction, JobOfferExtraction::STATUS_IMPORTED);

        return self::SUCCESS;
    }

    /**
     * @param string $source
     * @param string $configurationNameFromInput
     *
     * @return void
     */
    private function listUsedParameters(string $source, string $configurationNameFromInput): void
    {
        $this->io->listing([
            self::OPTION_LONG_KEYWORDS                      . ": " . json_encode($this->keywords),
            self::OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP . ": " . $this->maxPaginationPagesToScrap,
            self::OPTION_LONG_SOURCE                        . ": " . $source,
            self::OPTION_LONG_CONFIGURATION                 . ": " . $configurationNameFromInput,
            self::OPTION_LONG_NAME_LOCATION_NAME            . ": " . $this->locationName,
            self::OPTION_LONG_NAME_OFFERS_LIMIT             . ": " . $this->offersLimit,
            self::OPTION_LONG_NAME_DISTANCE                 . ": " . $this->distance,
        ]);
    }

    /**
     * Will create the entity used for extraction, or null on failure
     *
     * @param string $source
     * @param string $configurationNameFromInput
     *
     * @return JobOfferExtraction|null
     */
    private function createExtractionEntity(string $source, string $configurationNameFromInput): ?JobOfferExtraction
    {
        try {
            $jobOfferExtraction = $this->offerExtractionService->buildInitialExtractionEntity(
                [$source],
                $this->getJobSearchParameterBag(),
                JobOfferExtraction::TYPE_SINGLE,
                [$configurationNameFromInput],
            );
        }catch(Exception | TypeError $e){
            $this->loggerService->logException($e, [
                'info' => "Exception was thrown while building initial JobOfferExtraction"
            ]);
            return null;
        }

        return $jobOfferExtraction;
    }

    /**
     * Will handle search / extraction of offers for keywords
     *
     * @param string             $source
     * @param string             $configurationNameFromInput
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TerminateProcessException
     * @throws Exception
     */
    private function handleExtraction(string $source, string $configurationNameFromInput, JobOfferExtraction $jobOfferExtraction): void
    {
        $allSavedOffers = [];
        /**
         * Must be done like this since some jobs offer services might not return anything for keywords such as:
         * - vue php symfony (if all are being entered at once),
         *
         * But might return results for each keyword searched separately
         */
        foreach ($this->keywords as $keyword) {
            $this->io->info("Searching for results for keyword: {$keyword}");
            $newAndExistingOffersForConfigurations = $this->extractorServiceFactory->extractDataAndBuildJobOfferSearchResults(
                $source,
                $configurationNameFromInput,
                [$keyword],
                $jobOfferExtraction,
            );

            $jobOffers = $this->offerExtractionService->handleSearchResults(
                $newAndExistingOffersForConfigurations,
                $jobOfferExtraction,
                $keyword,
                $this->getJobSearchParameterBag(),
            );

            $allSavedOffers     = array_merge($allSavedOffers, $jobOffers);
            $countOfSavedOffers = count($jobOffers);

            $this->io->info("Saved {$countOfSavedOffers} job offers");

            $keyword2Configuration = $this->buildExtractionKeyword2Configuration(
                $keyword,
                $jobOfferExtraction,
                [$configurationNameFromInput],
                null,
                [],
                [$configurationNameFromInput]
            );

            $jobOfferExtraction->addKeyword2Configuration($keyword2Configuration);
        }

        $jobOfferExtraction->setPercentageDone(100);
        $this->offerExtractionService->updateExtraction($jobOfferExtraction);

        $countOfSavedOffers = count($allSavedOffers);
        $this->io->info(DateTimeProcessor::nowAsStringWrappedBetweenCharacters("[", "]") . "Finished extracting job offers - saved {$countOfSavedOffers} new offer/s");
    }

    /**
     * @return JobSearchParameterBag
     */
    private function getJobSearchParameterBag(): JobSearchParameterBag
    {
        $jobSearchParametersBag = new JobSearchParameterBag(
            keywords: $this->keywords,
            paginationPagesCount: $this->maxPaginationPagesToScrap,
            distance: $this->distance,
            location: $this->locationName,
            offersLimit: $this->offersLimit
        );

        return $jobSearchParametersBag;
    }
}