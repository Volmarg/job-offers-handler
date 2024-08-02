<?php

namespace JobSearcher\Service\JobSearch\Command\Extractor;

use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\JobService\ConfigurationBuilder\Api\ApiConfigurationBuilder;
use JobSearcher\Service\JobService\ConfigurationBuilder\DomHtml\DomHtmlConfigurationBuilder;
use Exception;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Factory for deciding which controller implementing the {@see ExtractorInterface} should be used to handle given logic
 */
class ExtractorServiceFactory
{

    /**
     * @var KernelInterface $kernel
     */
    private KernelInterface $kernel;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var ExtractorInterface[] $extractors
     */
    protected array $extractors = [];

    /**
     * @param ExtractorInterface[] $extractors
     */
    public function setExtractors(array $extractors): void
    {
        $this->extractors = $extractors;
    }

    /**
     * @param KernelInterface     $kernel
     * @param LoggerInterface     $logger
     * @param ConfigurationReader $configurationReader
     */
    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        private readonly ConfigurationReader $configurationReader
    ) {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    /**
     * Will decide which of the controllers implementing the {@see ExtractorInterface} will be used
     * to extract the job search results.
     *
     * @param string             $usedSource
     * @param string             $usedConfigurationName
     * @param array              $keywords
     * @param JobOfferExtraction $jobOfferExtraction
     *
     * @return NewAndExistingOffersDto[]
     * @throws Exception
     */
    public function extractDataAndBuildJobOfferSearchResults(
        string             $usedSource,
        string             $usedConfigurationName,
        array              $keywords,
        JobOfferExtraction $jobOfferExtraction
    ): array
    {
        if (!$this->validateInput($usedSource, $usedConfigurationName)) {
            return [];
        }

        $usedExtractorService = $this->getUsedExtractorService($usedSource);
        if (is_null($usedExtractorService)) {
            return [];
        }

        $configBuilderFqn = ExtractorInterface::SOURCE_TO_CONFIG_BUILDER_MAPPING[$usedSource];

        /**
         *  @var DomHtmlConfigurationBuilder | ApiConfigurationBuilder $configurationBuilder
         */
        $configurationBuilder = new $configBuilderFqn($this->kernel, $this->configurationReader);
        $configurationBuilder->loadAllConfigurations();

        $foundConfigurationDto = null;
        foreach($configurationBuilder->getJobSearchConfigurations() as $configurationName => $configurationDto){
            if($usedConfigurationName === $configurationName){
                $foundConfigurationDto = $configurationDto;
                break;
            }
        }

        if (empty($foundConfigurationDto)) {
            $this->logger->critical("No active configuration was found for given name", [
                "info"                                      => "configuration might be simply disabled",
                "source"                                    => $usedSource,
                "configurationName"                         => $usedConfigurationName,
                "supportedSourcesWithConfigurationBuilders" => ExtractorInterface::SOURCE_TO_CONFIG_BUILDER_MAPPING,
            ]);

            return [];
        }

        if (!$usedExtractorService->hasAnyConfigurationActive($foundConfigurationDto->getSupportedCountry())) {
            return [
                $usedConfigurationName => []
            ];
        }

        $newAndExistingOffersDto = $usedExtractorService->getSingleConfigSearchResults(
            $foundConfigurationDto,
            $keywords,
            $jobOfferExtraction->getPaginationPagesCount(),
            $jobOfferExtraction->getLocation(),
            $jobOfferExtraction->getDistance()
        );

        $returnedData = [
            $usedConfigurationName => $newAndExistingOffersDto
        ];

        return $returnedData;
    }

    /**
     * @param string $usedSource
     * @param string $usedConfigurationName
     *
     * @return bool
     * @throws Exception
     */
    private function validateInput(string $usedSource, string $usedConfigurationName): bool
    {
        if (!in_array($usedSource, ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES)) {
            $this->logger->critical("Given source is not supported!", [
                "got"           => $usedSource,
                "expectedOneOf" => ExtractorInterface::ALL_AVAILABLE_EXTRACTION_SOURCES,
            ]);

            return false;
        }

        $configurationsForSource = $this->configurationReader->getConfigurationNamesForTypeAndCountry(null, $usedSource);
        if (!in_array($usedConfigurationName, $configurationsForSource)) {
            $this->logger->critical("No configuration was found for source", [
                "source"                  => $usedSource,
                "configuration"           => $usedConfigurationName,
                "configurationsForSource" => $configurationsForSource,
            ]);

            return false;
        }

        if (!array_key_exists($usedSource, ExtractorInterface::SOURCE_TO_EXTRACTOR_CONTROLLER_MAPPING)) {
            $this->logger->critical("No controller implementing " . ExtractorInterface::class . " has been set for source mapping", [
                "sourceToExtractorControllerMapping" => ExtractorInterface::SOURCE_TO_EXTRACTOR_CONTROLLER_MAPPING,
                "source"                             => $usedSource,
            ]);

            return false;
        }

        if (!array_key_exists($usedSource, ExtractorInterface::SOURCE_TO_CONFIG_BUILDER_MAPPING)) {
            $this->logger->critical("This source is not supported as configuration builder", [
                "source"                                    => $usedSource,
                "supportedSourcesWithConfigurationBuilders" => ExtractorInterface::SOURCE_TO_CONFIG_BUILDER_MAPPING,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param string $usedSource
     *
     * @return ExtractorInterface|null
     */
    public function getUsedExtractorService(string $usedSource): ?ExtractorInterface
    {
        $controllerFqnForSource = ExtractorInterface::SOURCE_TO_EXTRACTOR_CONTROLLER_MAPPING[$usedSource];
        $usedExtractorService   = null;

        foreach ($this->extractors as $extractor) {
            if ($extractor::class === $controllerFqnForSource) {
                $usedExtractorService = $extractor;
                break;
            }
        }

        if (is_null($usedExtractorService)) {
            $extractorsClassesFromServicesYaml = array_map(
                fn(ExtractorInterface $extractor) => $extractor::class,
                $this->extractors,
            );

            $this->logger->critical("No controller implementing " . ExtractorInterface::class . " has been set in services.yml for source", [
                "source"                            => $usedSource,
                "extractorsClassesFromServicesYaml" => $extractorsClassesFromServicesYaml,
            ]);

            return null;
        }

        return $usedExtractorService;
    }

}