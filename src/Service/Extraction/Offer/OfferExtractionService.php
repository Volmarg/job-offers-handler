<?php

namespace JobSearcher\Service\Extraction\Offer;

use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\Command\JobSearch\AbstractJobSearchCommand;
use JobSearcher\Entity\Location\LocationDistance;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\General\GeneralSearchResult;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Exception\Bundle\ProxyProvider\ExternalProxyNotReachableException;
use JobSearcher\Exception\Extraction\TerminateProcessException;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Repository\Location\LocationDistanceRepository;
use JobSearcher\Service\Bundle\ProxyProvider\ProxyProviderService;
use JobSearcher\Service\DataProvider\JobSearchDataProviderService;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\Exception\ExceptionService;
use JobSearcher\Service\JobSearch\Result\JobSearchResultService;
use JobSearcher\Service\Languages\LanguageDetectionService;
use JobSearcher\Service\Location\LocationService;
use JobSearcher\Service\Math\Extraction\NewAndExistingOffersCounter;
use JobSearcher\Service\Shell\Command\ShellCommandService;
use JobSearcher\Service\Validation\ValidatorService;
use Monolog\Logger;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Handles the logic related to the {@see JobOfferExtraction}
 */
class OfferExtractionService
{

    public function __construct(
        private readonly JobSearchResultService          $jobSearchResultService,
        private readonly JobSearchDataProviderService    $jobSearchDataProviderService,
        private readonly LoggerInterface                 $logger,
        private readonly JobOfferExtractionRepository    $jobOfferExtractionRepository,
        private readonly ExtractionKeywords2OfferService $extractionKeywords2OfferService,
        private readonly EntityManagerInterface          $entityManager,
        private readonly ValidatorService                $validatorService,
        private readonly JobSearchResultRepository       $jobSearchResultRepository,
        private readonly LanguageDetectionService        $languageDetectionService,
        private readonly LocationService                 $locationService,
        private readonly LocationDistanceRepository      $locationDistanceRepository,
        private readonly ShellCommandService             $commandService,
        private readonly ProxyProviderService            $proxyProviderService
    )
    {}

    /**
     * Returns array of new saved offers
     *
     * @param NewAndExistingOffersDto[] $newAndExistingOffersForConfigurations
     * @param JobOfferExtraction        $jobOfferExtraction
     * @param string                    $keyword
     * @param JobSearchParameterBag     $jobSearchParametersBag
     *
     * @return array
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws ExternalProxyNotReachableException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TerminateProcessException
     */
    public function handleSearchResults(array $newAndExistingOffersForConfigurations, JobOfferExtraction $jobOfferExtraction, string $keyword, JobSearchParameterBag $jobSearchParametersBag): array
    {
        // the config names are later used for updating the extraction entity (adding the handled config name)
        $newSavedOffersForConfig = [];
        $newSavedOffers          = [];
        $lastHandledConfigName   = null;
        foreach ($newAndExistingOffersForConfigurations as $configuration => $newAndExistingOffersDto) {
            foreach ($newAndExistingOffersDto->getAllSearchResultDtos() as $searchResultDto) {
                $jobOffer = $this->handleNewSearchResult($searchResultDto, $configuration, $jobOfferExtraction);
                if (!is_null($jobOffer?->getId())) {
                    $newAndExistingOffersDto->addExistingOffer($jobOffer);
                    continue;
                }

                if (!empty($jobOffer)) {
                    $newSavedOffers[] = $jobOffer;
                    $this->entityManager->persist($jobOffer);

                    if (!array_key_exists($configuration, $newSavedOffersForConfig)) {
                        $newSavedOffersForConfig[$configuration] = [$jobOffer];
                        continue;
                    }

                    $newSavedOffersForConfig[$configuration][] = $jobOffer;
                }
            }

            // >WARNING< added due to known Deadlock issue, DON'T move it from here
            $this->entityManager->flush();
            $this->setExistingOfferKeywordRelations($newAndExistingOffersDto, $keyword, $jobOfferExtraction);

            // >WARNING< added due to known Deadlock issue, DON'T move it from here
            $this->entityManager->flush();

            $this->addExtractConfigWithNoNewOffers($lastHandledConfigName, $newSavedOffersForConfig, $configuration, $jobOfferExtraction);

            $lastHandledConfigName = $configuration;
        }

        /**
         * That's correct because if offers were found in other run these are still getting bound to extraction due to (n:n -> extraction:offers)
         * Also the amount of offers found via {@see NewAndExistingOffersDto::getExistingOfferEntities()} is not necessarily equal to the
         * {@see NewAndExistingOffersCounter::countBound} and that's because same offer
         * can be found on multiple job services and the {@see NewAndExistingOffersCounter::countBound} eliminates
         * the duplicates from being counted
         */
        $existingOffersCount = NewAndExistingOffersCounter::countBound($newAndExistingOffersForConfigurations, $jobOfferExtraction->getOfferIds());
        $newSavedOffersCount = count($newSavedOffers);
        $allOffersCount      = $newSavedOffersCount + $existingOffersCount;

        $this->updateExtraction($jobOfferExtraction, null, $allOffersCount, null, $newSavedOffersCount, $existingOffersCount);

        /**
         * >WARNING< This code block MUST stay here, it was moved here to solve the issue with deadlock.
         *           Testing this out is a nightmare, trust me You don't want to mess here up!
         */
        $this->handleOffersExtraData($newSavedOffersForConfig, $jobOfferExtraction, $keyword, $jobSearchParametersBag);

        return $newSavedOffers;
    }

    /**
     * Creates initial {@see JobOfferExtraction} which will store all the base information about offers extraction call
     *
     * @param array                 $sources
     * @param JobSearchParameterBag $jobSearchParameterBag
     * @param array                 $configurationNames
     * @param string                $extractionType
     *
     * @return JobOfferExtraction
     */
    public function buildInitialExtractionEntity(
        array                 $sources,
        JobSearchParameterBag $jobSearchParameterBag,
        string                $extractionType,
        array                 $configurationNames = [],
    ): JobOfferExtraction
    {
        $jobOfferExtraction = $this->create(
            $jobSearchParameterBag,
            $sources,
            $configurationNames,
            0,
            JobOfferExtraction::STATUS_IN_PROGRESS,
            $extractionType
        );

        return $jobOfferExtraction;
    }

    /**
     * Will create new entry of {@see JobOfferExtraction}
     *
     * @param JobSearchParameterBag $jobSearchParameterBag
     * @param array                 $sources
     * @param array                 $configurations
     * @param int                   $extractionCount
     * @param string                $status
     * @param string                $extractionType
     *
     * @return JobOfferExtraction
     */
    public function create(
        JobSearchParameterBag $jobSearchParameterBag,
        array                 $sources,
        array                 $configurations,
        int                   $extractionCount,
        string                $status,
        string                $extractionType,
    ): JobOfferExtraction
    {
        $jobOfferExtraction = new JobOfferExtraction();

        $jobOfferExtraction->setKeywords($jobSearchParameterBag->getKeywords());
        $jobOfferExtraction->setPaginationPagesCount($jobSearchParameterBag->getPaginationPagesCount());
        $jobOfferExtraction->setLocation($jobSearchParameterBag->getLocation());
        $jobOfferExtraction->setDistance($jobSearchParameterBag->getDistance());
        $jobOfferExtraction->setCountry($jobSearchParameterBag->getCountry());
        $jobOfferExtraction->setOffersLimit($jobSearchParameterBag->getOffersLimit());
        $jobOfferExtraction->setSources($sources);
        $jobOfferExtraction->setConfigurations($configurations);
        $jobOfferExtraction->setExtractionCount($extractionCount);
        $jobOfferExtraction->setStatus($status);
        $jobOfferExtraction->setType($extractionType);

        $this->jobOfferExtractionRepository->save($jobOfferExtraction);

        return $jobOfferExtraction;
    }

    /**
     * Will handle the single NEW search result
     * - transform it into entity,
     * - saving entity in DB,
     *
     * Returns saved entity, or NULL if something goes wrong or entry already exists in DB
     *
     * @param SearchResultDto       $resultDto
     * @param string                $configuration
     * @param JobOfferExtraction    $jobOfferExtraction
     *
     * @return JobSearchResult|null
     *
     * @throws TerminateProcessException
     */
    private function handleNewSearchResult(SearchResultDto $resultDto, string $configuration, JobOfferExtraction $jobOfferExtraction): ?JobSearchResult
    {
        try {
            $usedEntity = $this->jobSearchResultService->buildJobSearchResultFromSearchResultDto($resultDto, $configuration);
            $this->validateEntities($usedEntity);

            /**
             * It's not allowed that offer exists without company name in this project,
             * yet there are some offers in services without any company assigned to it,
             */
            if (empty($usedEntity->getCompany()?->getName())) {
                return null;
            }

            /**
             * @description this should generally not happen that offer exists for this data because this is already filtered in
             *              {@see JobSearchResultRepository::getIdsForCompanyNameAndJobTitleHashes}, keep in mind the {@see \DateTime}
             *              used there. On dev some extra offers are matching in here (current code that You look at),
             *              but that's most likely only due to the fact that no offers removal script is running.
             */
            $entityId = $this->jobSearchResultRepository->findFirstIdByJobTitleAndCompanyName(
                $resultDto->getJobDetailDto()->getJobTitle(),
                $resultDto->getCompanyDetailDto()->getCompanyName()
            );

            $isExistingEntity = false;
            if (!empty($entityId)) {
                $isExistingEntity = true;
                $usedEntity       = $this->jobSearchResultRepository->find($entityId);
            }

            $usedEntity->addExtraction($jobOfferExtraction);
            if (!$isExistingEntity) {
                if ($usedEntity instanceof GeneralSearchResult) {
                    $usedEntity->setConfigurationName($configuration);
                }
                $usedEntity->setFirstTimeFoundExtraction($jobOfferExtraction);
            }

            return $usedEntity;
        } catch (TerminateProcessException $tpe) {
            throw $tpe;
        } catch (Exception|TypeError $e) {
            $this->logger->critical("Something went wrong while trying to insert data to DB", [
                "searchResultDto" => $resultDto->toArray(),
                "exception"       => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ],
            ]);

            $this->captureClosingManager($e, $jobOfferExtraction);

            return null;
        }

    }

    /**
     * Issue with entity manager is that when it gets closed then only the FIRST exception before closing manager is
     * the real reason why it was closed.
     *
     * Trying to capture such exception and saving it on the extraction for debugging.
     *
     * @param Exception|TypeError $e
     * @param JobOfferExtraction  $jobOfferExtraction
     *
     * @throws TerminateProcessException
     */
    public function captureClosingManager(Exception|TypeError $e, JobOfferExtraction $jobOfferExtraction): void
    {
        if (
                !$this->entityManager->isOpen()
            &&
                (
                        !ExceptionService::isEntityManagerClosed($e)
                    ||  $e instanceof TerminateProcessException
                )
            &&  empty($jobOfferExtraction->getErrorTrace())
            &&  empty($jobOfferExtraction->getErrorMessage())
        ) {
            try {
                /** @var EntityManagerInterface $manager */
                $manager = $this->entityManager->create(
                    $this->entityManager->getConnection(),
                    $this->entityManager->getConfiguration()
                );

                /**
                 * It's closed manager, so it's beyond recoverable, thus detaching current state of extraction,
                 * fetching it anew with the last known state, then setting statuses, errors etc.
                 */
                $manager->clear(JobOfferExtraction::class);
                $reFetchedExtraction = $manager->find(JobOfferExtraction::class, $jobOfferExtraction->getId(), LockMode::NONE);

                $reFetchedExtraction->setErrorMessage($e->getMessage());
                $reFetchedExtraction->setErrorTrace($e->getTraceAsString());
                $reFetchedExtraction->setStatus(JobOfferExtraction::STATUS_FAILED);

                $manager->persist($reFetchedExtraction);
                $manager->flush();
            } catch (Exception|TypeError $e) {
                $this->logger->critical("Could not handle the broken entity manager state. Reason: {$e->getMessage()}!");
                throw new TerminateProcessException($e);
            }

            // re-throw to prevent from saving further found offers, the extraction is broken anyway
            throw new TerminateProcessException($e);
        }
    }

    /**
     * Will update the {@see JobOfferExtraction}, and save the updated version in DB
     * Keep in mind that this is not just "setting" counts, but sums it with already present count,
     *
     * @param JobOfferExtraction $jobOfferExtraction
     * @param string|null $status
     * @param int|null $extractionCount
     * @param array|null $configurations
     * @param int|null $newOffersCount
     * @param int|null $boundUniqueOffersCount
     *
     * @return JobOfferExtraction
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateExtraction(
        JobOfferExtraction $jobOfferExtraction,
        ?string            $status = null,
        ?int               $extractionCount = null,
        ?array             $configurations = null,
        ?int               $newOffersCount = null,
        ?int               $boundUniqueOffersCount = null
    ): JobOfferExtraction
    {
        if (!is_null($status)) {
            $jobOfferExtraction->setStatus($status);
        }

        if (!is_null($extractionCount)) {
            $totalCount = $jobOfferExtraction->getExtractionCount() + $extractionCount;
            $jobOfferExtraction->setExtractionCount($totalCount);
        }

        if (!is_null($configurations)) {
            $currentConfigurations = $jobOfferExtraction->getConfigurations() ?? [];
            $allConfigurations     = array_unique([
                ...$currentConfigurations,
                ...$configurations
            ]);
            $jobOfferExtraction->setConfigurations($allConfigurations);
        }

        if (!is_null($newOffersCount)){
            $allNewOffersCount = $jobOfferExtraction->getNewOffersCount() + $newOffersCount;
            $jobOfferExtraction->setNewOffersCount($allNewOffersCount);
        }

        if (!is_null($boundUniqueOffersCount)) {
            $allBoundUniqueOffersCount = $jobOfferExtraction->getBoundOffersCount() + $boundUniqueOffersCount;
            $jobOfferExtraction->setBoundOffersCount($allBoundUniqueOffersCount);
        }

        $this->jobOfferExtractionRepository->save($jobOfferExtraction);

        return $jobOfferExtraction;
    }

    /**
     * Validating entities related to offer, created from {@see SearchResultDto}.
     * This data has to be validated before persisting, otherwise might cause {@see ConstraintViolationException}
     * and then {@see EntityManagerInterface} will be closed, this causes lot of mess and breaks whole offers processing,
     *
     * It's impossible to re-open closed {@see EntityManagerInterface} - already tried,
     *
     * @param JobSearchResult $jobOffer
     *
     * @return void
     * @throws Exception
     */
    private function validateEntities(JobSearchResult $jobOffer): void
    {
        $allViolations = [];
        foreach ($jobOffer->getLocations() as $index => $location) {
            $locationValidationResult = $this->validatorService
                ->validateAndReturnArrayOfInvalidFieldsWithMessages($location);

            if (!$locationValidationResult->isSuccess()) {
                $allViolations["jobOfferLocation_${index}"] = $locationValidationResult->getViolationsWithMessages();
            }
        }

        $branches = $jobOffer->getCompany()?->getCompanyBranches() ?? [];
        foreach ($branches as $index => $branch) {
            $branchValidationResult = $this->validatorService
                ->validateAndReturnArrayOfInvalidFieldsWithMessages($branch);

            if (!$branchValidationResult->isSuccess()) {
                $allViolations["jobOfferCompanyBranch_${index}"] = $branchValidationResult->getViolationsWithMessages();
            }
        }

        $jobOfferValidationResult = $this->validatorService
            ->validateAndReturnArrayOfInvalidFieldsWithMessages($jobOffer);

        if (!$jobOfferValidationResult->isSuccess()) {
            $allViolations["jobOffer"] = $jobOfferValidationResult->getViolationsWithMessages();
        }


        if (!empty($jobOffer->getCompany())) {
            $jobOfferCompanyValidationResult = $this->validatorService
                ->validateAndReturnArrayOfInvalidFieldsWithMessages($jobOffer->getCompany());

            if (!$jobOfferCompanyValidationResult->isSuccess()) {
                $allViolations["jobOfferCompany"] = $jobOfferCompanyValidationResult->getViolationsWithMessages();
            }
        }

        if (!empty($jobOffer->getCompanyBranch())) {
            $jobOfferBranchValidationResult = $this->validatorService
                ->validateAndReturnArrayOfInvalidFieldsWithMessages($jobOffer->getCompanyBranch());

            if (!$jobOfferBranchValidationResult->isSuccess()) {
                $allViolations["jobOfferBranch"] = $jobOfferBranchValidationResult->getViolationsWithMessages();
            }
        }

        if (!empty($allViolations)) {
            $message = "Job offer or/and its related data constraints are violated, this data will NOT be persisted";
            $this->logger->warning($message, [
                "violations"  => $allViolations,
                "jobOfferUrl" => $jobOffer->getJobOfferUrl(),
            ]);
            throw new Exception($message);
        }
    }

    /**
     * Info: Is for now disabled due to insane requirements to self-host the maps
     * - {@link https://nominatim.org/release-docs/latest/admin/Installation/} (Full world = 64GB RAM, 1TB SSD)
     * - Europe seems to be significantly smaller so maybe at some point in future...
     *
     * If the search is made with distance then the distance result will be stored In DB,
     * The "search by distance" is based on "internal job service website" distance search,
     * So if the website / api already provides such functionality then:
     * - saving calls to the api for checking the distance,
     * - slowly filling up the DB with location distance,
     * - the location distances based on web services will most likely be very accurate
     *
     * The result of this method is:
     * - creating {@see LocationDistance},
     * - creating or fetching new {@see Location} for {@see JobSearchParameterBag::$location},
     * - persisting the {@see LocationDistance}, but not yet flushing to prevent abusive / expensive INSERTION,
     *   the flushing is done anyway somewhere in the code responsible for creating {@see JobSearchResult} from {@see SearchResultDto}
     *
     * @param JobSearchResult $entity
     * @param JobSearchParameterBag $jobSearchParametersBag
     *
     * @return void
     * @throws NonUniqueResultException
     */
    private function handleLocationDistance(JobSearchResult $entity, JobSearchParameterBag $jobSearchParametersBag): void
    {
        return;
        if (!$entity->hasLocation()) {
            return;
        }

        $locationFromExtraction = $this->locationService->findOneByLocationName($jobSearchParametersBag->getLocation());
        if (empty($locationFromExtraction)) {
            return;
        }

        $savedLocationHashes = [];
        foreach ($entity->getLocations() as $offerLocation) {

            $existingLocationDistance = $this->locationDistanceRepository->findByLocations($offerLocation, $locationFromExtraction);
            if (!empty($existingLocationDistance)) {
                continue;
            }

            $savedLocationHash         = md5($offerLocation->getId() . $locationFromExtraction->getId());
            $reversedSavedLocationHash = md5($locationFromExtraction->getId() . $offerLocation->getId());

            if(
                    in_array($savedLocationHash, $savedLocationHashes)
                ||  in_array($reversedSavedLocationHash, $savedLocationHashes)
            ){
                continue;
            }

            $locationDistance = new LocationDistance();
            $locationDistance->setDistance($jobSearchParametersBag->getDistance());
            $locationDistance->setFirstLocation($offerLocation);
            $locationDistance->setSecondLocation($locationFromExtraction);
            $locationDistance->setOfferServiceBased(true);

            $this->entityManager->persist($locationDistance);

            $savedLocationHashes[] = $savedLocationHash;
            $savedLocationHashes[] = $reversedSavedLocationHash;
        }
    }

    /**
     * @param JobSearchResult[]     $newSavedOffersForConfig
     * @param JobOfferExtraction    $jobOfferExtraction
     * @param string                $keyword
     * @param JobSearchParameterBag $jobSearchParametersBag
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     * @throws NotFoundExceptionInterface
     * @throws TerminateProcessException
     * @throws GuzzleException
     * @throws ExternalProxyNotReachableException
     * @throws Exception
     */
    private function handleOffersExtraData(
        array                 $newSavedOffersForConfig,
        JobOfferExtraction    $jobOfferExtraction,
        string                $keyword,
        JobSearchParameterBag $jobSearchParametersBag
    ): void {
        foreach ($newSavedOffersForConfig as $config => $savedOffers) {
            foreach ($savedOffers as $savedOffer) {

                $this->proxyProviderService->checkProxyReachability();
                if (!EnvReader::canFetchOffersExtraData()) {
                    $this->entityManager->persist($savedOffer);
                    $this->entityManager->flush();
                    continue;
                }

                $this->languageDetectionService->getForOffers([$savedOffer]);
                $this->jobSearchDataProviderService->provideForSingleOffer($savedOffer, $jobOfferExtraction);

                $keyword2Offer = $this->extractionKeywords2OfferService->createEntity($keyword, $savedOffer, $jobOfferExtraction);
                $keyword2Offer->setExtraction($jobOfferExtraction);

                if ($jobSearchParametersBag->isDistanceSet()) {
                    $this->handleLocationDistance($savedOffer, $jobSearchParametersBag);
                }

                $this->entityManager->persist($savedOffer);
                $this->entityManager->flush();
            }

            $this->logger->info("[Extraction {$jobOfferExtraction->getId()}] Adding finished config: {$config}");
            $jobOfferExtraction->addConfiguration($config);
            $this->entityManager->persist($jobOfferExtraction);
            $this->entityManager->flush();
        }
    }

    /**
     * Calls shell to check if there is any extraction currently running
     *
     * @return bool
     *
     * @throws Exception
     */
    public function isAnyExtractionRunning(): bool
    {
        // need to exclude grep itself else it will find the process running (which will invalidly point to this check command)
        $cliCommand = "ps aux | grep 'bin/console' | grep 'php' | grep -v 'grep' |  grep '" . AbstractJobSearchCommand::COMMON_COMMAND_NAME_PART  . "'";

        // setting logger level to info, because this causes error code if nothing is running, else will get tones of false critical emails
        $isRunning  =  $this->commandService->executeWithLoggedOutput($cliCommand, null, Logger::INFO);

        return $isRunning;
    }

    /**
     * These methods are used to determine if offers are already saved (are existing)
     * {@see JobSearchResultRepository::getIdsForCompanyNameAndJobTitleHashes()}
     * {@see JobSearchResultRepository::getExistingOfferIdsForUrls()}
     *
     * The already existing offers are not re-linked to extraction. Only some meta-data is getting stored.
     *
     * @param NewAndExistingOffersDto $newAndExistingOffersDto
     * @param string                  $keyword
     * @param JobOfferExtraction      $jobOfferExtraction
     */
    private function setExistingOfferKeywordRelations(NewAndExistingOffersDto $newAndExistingOffersDto, string $keyword, JobOfferExtraction $jobOfferExtraction): void
    {
        foreach ($newAndExistingOffersDto->getExistingOfferEntities() as $existingOfferEntity) {
            $keyword2Offer = $this->extractionKeywords2OfferService->createEntity($keyword, $existingOfferEntity, $jobOfferExtraction);
            $keyword2Offer->setExtraction($jobOfferExtraction);

            // existing offers are getting bound to new extraction run, because that run HAS TO return results as well
            $jobOfferExtraction->addJobSearchResult($existingOfferEntity);
            $existingOfferEntity->addExtraction($jobOfferExtraction);

            $this->entityManager->persist($jobOfferExtraction);
        }
    }

    /**
     * Mark handled configurations in case when offers for that config are fully bound from other searches
     *
     * @param string|null        $lastHandledConfigName
     * @param array              $newSavedOffersForConfig
     * @param string             $configuration
     * @param JobOfferExtraction $jobOfferExtraction
     */
    public function addExtractConfigWithNoNewOffers(
        ?string            $lastHandledConfigName,
        array              $newSavedOffersForConfig,
        string             $configuration,
        JobOfferExtraction $jobOfferExtraction
    ): void {
        if (!is_null($lastHandledConfigName) && !isset($newSavedOffersForConfig[$lastHandledConfigName]) && ($lastHandledConfigName !== $configuration)) {
            $this->logger->info("[Extraction {$jobOfferExtraction->getId()}] Adding finished config: {$lastHandledConfigName}");
            $jobOfferExtraction->addConfiguration($lastHandledConfigName);
            $this->entityManager->persist($jobOfferExtraction);
            $this->entityManager->flush();
        }
    }
}