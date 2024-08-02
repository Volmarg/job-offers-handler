<?php

namespace JobSearcher\Command\JobSearch;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\Entity\Extraction\Extraction2AmqpRequest;
use JobSearcher\Entity\Storage\AmqpStorage;
use JobSearcher\Exception\Bundle\ProxyProvider\ExternalProxyNotReachableException;
use JobSearcher\Exception\Extraction\TerminateProcessException;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\Extraction\Offer\ExtractionProgressDeciderService;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;
use Exception;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use JobSearcher\Service\Math\Extraction\NewAndExistingOffersCounter;
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
 * Handles extracting the job offers and storing them in database
 */
class AllJobOffersExtractorCommand extends AbstractJobSearchCommand
{
    public const COMMAND_NAME = "all-" . self::COMMON_COMMAND_NAME_PART;

    public const OPTION_COUNTRY = "country";

    public const OPTION_AMQP_STORAGE_ID = "amqp-id";

    /**
     * @var string|null
     */
    protected ?string $country = null;

    /**
     * @var int|null
     */
    private ?int $amqpId = null;


    /**
     * {@see AllJobOffersExtractorCommand::getExtractionSourceWithActiveConfigurations()}
     *
     * @var array
     */
    private array $nonEmptyExtractionSources = [];

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface                  $logger
     * @param ExtractionProgressDeciderService $extractionProgressDeciderService
     * @param OfferExtractionService           $extractionService
     * @param EntityManagerInterface           $entityManager
     * @param ConfigurationReader              $configurationReader
     * @param LoggerService                    $loggerService
     */
    public function __construct(
        LoggerInterface                         $logger,
        ExtractionProgressDeciderService        $extractionProgressDeciderService,
        private readonly OfferExtractionService $extractionService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfigurationReader    $configurationReader,
        private readonly LoggerService          $loggerService
    )
    {
        parent::__construct($logger, $configurationReader, $extractionProgressDeciderService);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription("Will handle extracting job offers for all source types and all active configurations");
        $this->addOption(self::OPTION_COUNTRY, null, InputOption::VALUE_REQUIRED, "Country for which the offers should be fetched");
        $this->addOption(self::OPTION_AMQP_STORAGE_ID, null, InputOption::VALUE_OPTIONAL, "Related, EXISTING amqp storage id to bind this extraction to");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->country                   = $input->getOption(self::OPTION_COUNTRY) ?? "";
        $this->amqpId                    = $input->getOption(self::OPTION_AMQP_STORAGE_ID) ?? null;
        $allSupportedCountries           = $this->configurationReader->getSupportedCountries();
        $this->nonEmptyExtractionSources = $this->getExtractionSourceWithActiveConfigurations();

        if (empty($this->country)) {
            $message = "No country was provided";
            $this->logger->critical($message);
            throw new Exception($message);
        }

        if (!in_array($this->country, $allSupportedCountries)) {
            $message= "This country is not supported: `{$this->country}`. Allowed countries are: " . json_encode($allSupportedCountries);
            $this->logger->critical($message);
            throw new Exception($message);
        }

    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TerminateProcessException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (EnvReader::isDemo()) {
            return self::FAILURE;
        }

        try {
            $jobOfferExtraction = $this->extractionService->buildInitialExtractionEntity(
                $this->nonEmptyExtractionSources,
                $this->getJobSearchParameterBag(),
                JobOfferExtraction::TYPE_ALL
            );

            $this->bindExtractionToAmqpStorage($jobOfferExtraction);

            $configurationNames = $this->getAllConfigurationNames();
        } catch (Exception|TypeError $e) {
            if (isset($jobOfferExtraction)) {
                $this->extractionService->updateExtraction($jobOfferExtraction, JobOfferExtraction::STATUS_FAILED);
            }

            $this->loggerService->logException($e, [
                'info' => "Exception was thrown while building initial JobOfferExtraction"
            ]);

            return self::FAILURE;
        }

        try {
            $this->io->info(DateTimeProcessor::nowAsStringWrappedBetweenCharacters("[","]") . "Started extracting job offers");
            $this->listUsedParameters();
            $this->handleExtraction($jobOfferExtraction);

            $this->decideExtractionProgress($jobOfferExtraction, $this->country, $this->nonEmptyExtractionSources, $configurationNames);
            $this->extractionService->updateExtraction($jobOfferExtraction);
        } catch (TerminateProcessException $tpe) {
            $this->loggerService->logException($tpe);
            $this->decideExtractionProgress($jobOfferExtraction, $this->country, $this->nonEmptyExtractionSources, $configurationNames);
            $this->extractionService->captureClosingManager($tpe, $jobOfferExtraction);
            return self::FAILURE;
        } catch(ExternalProxyNotReachableException $epr) {
            $this->loggerService->logException($epr);
            $this->decideExtractionProgress($jobOfferExtraction, $this->country, $this->nonEmptyExtractionSources, $configurationNames);
            return self::FAILURE;
        } catch (Exception|TypeError $e) {
            $this->loggerService->logException($e);
            $this->decideExtractionProgress($jobOfferExtraction, $this->country, $this->nonEmptyExtractionSources, $configurationNames);
            $this->extractionService->updateExtraction($jobOfferExtraction, JobOfferExtraction::STATUS_FAILED);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return void
     */
    private function listUsedParameters(): void
    {
        $this->io->listing([
            self::OPTION_LONG_KEYWORDS                      . ":" . json_encode($this->keywords),
            self::OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP . ":" . $this->maxPaginationPagesToScrap,
            self::OPTION_COUNTRY                            . ":" . $this->country,
            self::OPTION_LONG_NAME_LOCATION_NAME            . ":" . $this->locationName,
            self::OPTION_LONG_NAME_DISTANCE                 . ":" . $this->distance,
        ]);
    }

    /**
     * Will handle search / extraction of offers for keywords & extractors
     *
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TerminateProcessException
     * @throws ExternalProxyNotReachableException
     * @throws GuzzleException
     */
    private function handleExtraction(JobOfferExtraction $jobOfferExtraction): void
    {
        $allNewSavedOffers = [];
        foreach ($this->extractors as $extractor) {
            if (!$extractor->hasAnyConfigurationActive($jobOfferExtraction->getCountry())) {
                $this->logger->info("There are no active configuration for this extractor, skipping it. Extractor source: " . $extractor->getExtractionSourceName());
                continue;
            }

            $this->io->note("Now extracting data for extractor: " . $extractor::class);

            try{
                /**
                 * Must be done like this since some jobs offer services might not return anything for keywords such as:
                 * - vue php symfony (if all are being entered at once),
                 *
                 * But might return results for each keyword searched separately
                 */
                foreach ($this->keywords as $keyword) {
                    $allNewSavedOffers = $this->handleSearchedKeyword($keyword, $extractor, $jobOfferExtraction, $allNewSavedOffers);
                }
            } catch (TerminateProcessException $tpe) {
                throw $tpe;
            } catch (ExternalProxyNotReachableException $ep) {
                throw $ep;
            } catch(Exception | TypeError $er){
                $this->logger->critical("Exception was thrown while extracting job offers for single extractor. This extractor will be skipped. Continuing with next one", [
                    "skippedExtractor" => $extractor::class,
                    "class"            => self::class,
                    "exception"        => [
                        "message"       => $er->getMessage(),
                        "trace"         => $er->getTraceAsString(),
                    ]
                ]);

                $this->extractionService->captureClosingManager($er, $jobOfferExtraction);
                continue;
            }

        }

        $this->entityManager->flush();
        $this->extractionService->updateExtraction($jobOfferExtraction, JobOfferExtraction::STATUS_IMPORTED);

        $countOfSavedOffers = count($allNewSavedOffers);
        $this->io->info(DateTimeProcessor::nowAsStringWrappedBetweenCharacters("[","]") . "Finished extracting job offers - saved {$countOfSavedOffers} new offer/s");
    }

    /**
     * @return JobSearchParameterBag
     */
    private function getJobSearchParameterBag(): JobSearchParameterBag
    {
        $jobSearchParametersBag = new JobSearchParameterBag(
            $this->keywords,
            $this->maxPaginationPagesToScrap,
            $this->distance,
            $this->locationName,
            $this->country,
            $this->offersLimit
        );

        return $jobSearchParametersBag;
    }

    /**
     * Will set relation between {@see JobOfferExtraction} and {@see AmqpStorage} in {@see Extraction2AmqpRequest}
     *
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @throws Exception
     */
    private function bindExtractionToAmqpStorage(JobOfferExtraction $jobOfferExtraction): void
    {
        if (empty($this->amqpId)) {
            return;
        }

        $storageRepo  = $this->entityManager->getRepository(AmqpStorage::class);
        $storageEntry = $storageRepo->find($this->amqpId);
        if (empty($storageEntry)) {
            throw new Exception("Invalid amqp storage id given, no entity exists for it. Id: {$this->amqpId}");
        }

        $extraction2aqmp = new Extraction2AmqpRequest();
        $extraction2aqmp->setExtraction($jobOfferExtraction);
        $extraction2aqmp->setAmqpRequest($storageEntry);

        $this->entityManager->persist($extraction2aqmp);
        $this->entityManager->flush();
    }

    /**
     * Will return only the extraction sources which got some configurations active in them.
     * So for example, only one or none would get returned from {@see ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES}
     *
     * @return array
     *
     * @throws Exception
     */
    private function getExtractionSourceWithActiveConfigurations(): array
    {
        $nonEmptySources = [];
        foreach (ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES as $sourceType) {
            $configurationNames = $this->configurationReader->getConfigurationNamesForTypes($this->country, [$sourceType]);
            if (empty($configurationNames)) {
                continue;
            }

            $nonEmptySources[] = $sourceType;
        }

        return $nonEmptySources;
    }

    /**
     * Pretty much {@see ExtractorInterface::getAllConfigurationNames()}, but in here it's being called for all the
     * supported extractors.
     *
     * @return array
     */
    private function getAllConfigurationNames(): array
    {
        $configurationNames = [];
        foreach ($this->extractors as $extractor) {
            $configurationNames = [
                ...$configurationNames,
                ...$extractor->getAllConfigurationNames($this->country)
            ];
        }

        return $configurationNames;
    }

    /**
     * @param string             $keyword
     * @param ExtractorInterface $extractor
     * @param JobOfferExtraction $jobOfferExtraction
     * @param array              $allNewSavedOffers
     *
     * @return array
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws ExternalProxyNotReachableException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TerminateProcessException
     * @throws NonUniqueResultException
     * @throws GuzzleException
     * @throws Exception
     */
    private function handleSearchedKeyword(string $keyword, ExtractorInterface $extractor, JobOfferExtraction $jobOfferExtraction, array $allNewSavedOffers): array
    {
        $this->io->info("Search for job offers for keyword: {$keyword}");
        $newAndExistingOffersForConfigurations = $extractor->getOffersForAllConfigurations(
            [$keyword],
            $this->maxPaginationPagesToScrap,
            $jobOfferExtraction
        );

        $newSavedOffers = $this->extractionService->handleSearchResults($newAndExistingOffersForConfigurations, $jobOfferExtraction, $keyword, $this->getJobSearchParameterBag());

        $newSavedOffersCount = count($newSavedOffers);
        $existingOffersCount = NewAndExistingOffersCounter::countBound($newAndExistingOffersForConfigurations, $jobOfferExtraction->getOfferIds());
        $allNewSavedOffers   = array_merge($newSavedOffers, $allNewSavedOffers);

        $this->io->listing([
            "Keyword   : {$keyword}",
            "Extractor : " . $extractor::class,
            "Saved     : {$newSavedOffersCount} new job offers",
            "Bound     : {$existingOffersCount} existing job offers",
        ]);

        $configurations     = array_keys($newAndExistingOffersForConfigurations);

        $keyword2Configuration = $this->buildExtractionKeyword2Configuration(
            $keyword,
            $jobOfferExtraction,
            $configurations,
            $this->country,
            [$extractor->getExtractionSourceName()]
        );

        $jobOfferExtraction->addKeyword2Configuration($keyword2Configuration);

        return $allNewSavedOffers;
    }

}