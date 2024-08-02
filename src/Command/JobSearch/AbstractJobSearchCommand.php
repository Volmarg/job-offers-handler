<?php

namespace JobSearcher\Command\JobSearch;

use JobSearcher\Command\AbstractCommand;
use JobSearcher\Entity\Extraction\ExtractionKeyword2Configuration;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\Extraction\Offer\ExtractionProgressDeciderService;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;
use Exception;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Common logic for job offer search command
 */
abstract class AbstractJobSearchCommand extends AbstractCommand
{
    public const COMMON_COMMAND_NAME_PART = "job-offers-extractor";

    const OPTION_LONG_KEYWORDS  = "keywords";
    const OPTION_SHORT_KEYWORDS = "kw";

    const OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP  = "max-pagination-pages-to-scrap";
    const OPTION_SHORT_MAX_PAGINATION_PAGES_TO_SCRAP = "max-page";

    const OPTION_LONG_NAME_LOCATION_NAME = "location-name";
    const OPTION_LONG_NAME_DISTANCE      = "distance";
    const OPTION_LONG_NAME_OFFERS_LIMIT  = "offers-limit";

    /**
     * @var ExtractorInterface[] $extractors
     */
    protected array $extractors = [];

    /**
     * @var array $keywords
     */
    protected array $keywords = [];

    /**
     * @var int $maxPaginationPagesToScrap
     */
    protected int $maxPaginationPagesToScrap = 0;

    protected ?string $locationName = null;
    protected ?int    $distance     = null;
    protected ?int    $offersLimit  = null;

    /**
     * @param ExtractorInterface[] $extractors
     */
    public function setExtractors(array $extractors): void
    {
        $this->extractors = $extractors;
    }

    /**
     * @param LoggerInterface                  $logger
     * @param ConfigurationReader              $configurationReader
     * @param ExtractionProgressDeciderService $extractionProgressDeciderService
     */
    public function __construct(
        LoggerInterface                                   $logger,
        private readonly ConfigurationReader              $configurationReader,
        private readonly ExtractionProgressDeciderService $extractionProgressDeciderService,
    )
    {
        parent::__construct($logger);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $keywordsString                  = $input->getOption(self::OPTION_LONG_KEYWORDS);
        $offersLimit                     = $input->getOption(self::OPTION_LONG_NAME_OFFERS_LIMIT);
        $this->maxPaginationPagesToScrap = $input->getOption(self::OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP);
        $this->locationName              = $input->getOption(self::OPTION_LONG_NAME_LOCATION_NAME);
        $this->distance                  = $input->getOption(self::OPTION_LONG_NAME_DISTANCE);

        if (
                empty($this->locationName)
            && !empty($this->distance)
        ) {
            throw new LogicException("Providing distance without location is not allowed!");
        }

        if( is_null($this->maxPaginationPagesToScrap) ){
            $message = "No max pagination to scrap has been provided!";
            $this->logger->critical($message);
            throw new Exception($message);
        }

        $this->keywords = explode(",", $keywordsString);
        if( empty($this->keywords) ){

            $message = "No keywords were provided!";
            $this->logger->critical($message, [
                "keywordsString" => $keywordsString,
            ]);

            throw new Exception($message);
        }

        if (!empty($offersLimit)) {
            $this->offersLimit = (int)$offersLimit;
        }

    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_LONG_KEYWORDS, self::OPTION_SHORT_KEYWORDS, InputOption::VALUE_REQUIRED, "Keywords for which job offers will be searched form", '')
             ->addOption(self::OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP, self::OPTION_SHORT_MAX_PAGINATION_PAGES_TO_SCRAP, InputOption::VALUE_REQUIRED, "Number of pagination pages to analyze for the job offers")
             ->addOption(self::OPTION_LONG_NAME_LOCATION_NAME, null, InputOption::VALUE_REQUIRED, "Location name for which offers should be searched for")
             ->addOption(self::OPTION_LONG_NAME_DISTANCE, null, InputOption::VALUE_REQUIRED, "Max distance (KM) from provided locationName - offers will be limited to that by job offer services")
             ->addOption(self::OPTION_LONG_NAME_OFFERS_LIMIT, null, InputOption::VALUE_OPTIONAL, "Max offers to search for")
             ->addUsage("--" . self::OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP . "=0 --" . self::OPTION_LONG_KEYWORDS . "='php,js'")
             ->addUsage("--" . self::OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP . "=0 --" . self::OPTION_LONG_KEYWORDS . "='php,js' --" . self::OPTION_LONG_NAME_LOCATION_NAME . "=dresden --" . self::OPTION_LONG_NAME_DISTANCE . "=23" . " --" . self::OPTION_LONG_NAME_OFFERS_LIMIT . "=35");
    }

    /**
     * Decides what's the overall extraction percentage, covers multiple cases / deciding logic:
     * - comparing "which configurations were expected, and how many of them were handled",
     * - considering "only part of configuration were handled but yielded a lot of offers"
     *
     * @param JobOfferExtraction $extraction
     * @param string|null        $country
     * @param array              $targetSources
     * @param array              $targetConfigurationNames
     *
     * @return JobOfferExtraction
     * @throws Exception
     */
    protected function decideExtractionProgress(
        JobOfferExtraction $extraction,
        ?string            $country = null,
        array              $targetSources = [],
        array              $targetConfigurationNames = [],
    ): JobOfferExtraction
    {
        if (!$extraction->isValidRunTime()) {
            $msg = "Extraction run time is invalid. Script was most likely instantly terminated.";
            $this->logger->critical($msg);

            $extraction->setStatus(JobOfferExtraction::STATUS_FAILED);
            $extraction->setErrorMessage($msg);

            return $extraction;
        }

        $this->extractionProgressDeciderService->init(
            $extraction,
            $this->keywords,
            $country,
            $targetSources,
            $targetConfigurationNames
        );

        $percentage = $this->extractionProgressDeciderService->decide();
        $extraction->setPercentageDone((int)$percentage);

        if ($percentage < 100) {
            // just being fair here, let face it, if it's below 100 then user deserves some re-found and status should be set properly
            $extraction->setStatus(JobOfferExtraction::STATUS_PARTIALLY_IMPORTED);
        }

        return $extraction;
    }

    /**
     * Builds {@see ExtractionKeyword2Configuration}
     *
     * @param string             $keyword
     * @param JobOfferExtraction $extraction
     * @param array              $configurations
     * @param string|null        $country
     * @param array              $targetSources
     * @param array              $targetConfigurations
     *
     * @return ExtractionKeyword2Configuration
     *
     * @throws Exception
     */
    protected function buildExtractionKeyword2Configuration(
        string             $keyword,
        JobOfferExtraction $extraction,
        array              $configurations,
        ?string            $country,
        array              $targetSources,
        array              $targetConfigurations = []
    ): ExtractionKeyword2Configuration
    {
        $expectedConfigurations = $targetConfigurations;
        if (empty($expectedConfigurations)) {
            $expectedConfigurations = $this->configurationReader->getConfigurationNamesForTypes($country, $targetSources);
        }

        if (empty($expectedConfigurations)) {
            $data = json_encode([
                'targetSources' => $targetSources,
                'country'       => $country,
            ]);
            throw new LogicException("Expected configurations are empty. Data: {$data}. Extraction: {$extraction->getId()}");
        }

        $keyword2Configuration = new ExtractionKeyword2Configuration();
        $keyword2Configuration->setKeyword($keyword);
        $keyword2Configuration->setExpectedConfigurations($expectedConfigurations);
        $keyword2Configuration->setExtraction($extraction);
        $keyword2Configuration->setConfigurations($configurations);

        return $keyword2Configuration;
    }

}