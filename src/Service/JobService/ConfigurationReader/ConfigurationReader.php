<?php

namespace JobSearcher\Service\JobService\ConfigurationReader;

use Exception;
use JobSearcher\Service\JobService\ConfigurationBuilder\AbstractConfigurationBuilder;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use LogicException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurationReader
{
    /**
     * This was added to avoid calling the {@see Finder} multiple times as it turns out it was causing
     * around 50% memory increased usage.
     *
     * @var array
     */
    private static array $CONFIGURATION_FILES_FOR_TYPE = [];

    public const KEY_JOB_SERVICES_CONFIGURATIONS_FILES_FOLDER_NAME = "jobServices";

    private const CONFIGURATION_FILE_PATH_COUNTRY_NAME_REGEXP = self::KEY_JOB_SERVICES_CONFIGURATIONS_FILES_FOLDER_NAME
                                                              . DIRECTORY_SEPARATOR
                                                              . "(?<TYPE>[a-zA-Z]*)"
                                                              . "/(?<COUNTRY_NAME>[a-zA-Z]*)";

    /**
     * Will return pattern that can be used to check if given folder/file path contains given config type name
     *
     * @param string $configurationType
     *
     * @return string
     */
    public function getConfigurationTypeMatchPattern(string $configurationType): string
    {
        return self::KEY_JOB_SERVICES_CONFIGURATIONS_FILES_FOLDER_NAME . DIRECTORY_SEPARATOR . strtolower($configurationType);
    }

    public function __construct(
        private readonly KernelInterface $kernel
    ){}

    /**
     * Will return all the configuration files paths
     *
     * @throws Exception
     */
    public function getAllConfigurationFilesPaths(): array
    {
        $configurationFilePaths = [];
        $configurationFilesPathsForTypes = $this->getConfigurationFilesPathsForCountry();
        foreach($configurationFilesPathsForTypes as $type => $filePath){
            $configurationFilePaths[$type] = $filePath;
        }

        return $configurationFilePaths;
    }

    /**
     * Will return configuration files paths for given country and configuration type (api / dom etc.)
     *
     * @param string|null $country
     * @param string      $configurationType
     *
     * @return array
     * @throws Exception
     */
    public function getConfigurationFilesPathsForTypeAndCountry(?string $country, string $configurationType): array
    {
        $configurationFilePaths             = [];
        $allConfigurationFilesPathsForTypes = $this->getConfigurationFilesPathsForCountry($country);
        foreach ($allConfigurationFilesPathsForTypes as $filePaths) {
            foreach ($filePaths as $filePath) {
                if (!str_contains($filePath, $this->getConfigurationTypeMatchPattern($configurationType))) {
                    continue;
                }
                $configurationFilePaths[] = $filePath;
            }
        }

        return $configurationFilePaths;
    }

    /**
     * Will return configuration files paths for given country and configuration type (api / dom etc.)
     *
     * @param string|null $country
     * @param string      $configurationType
     *
     * @return array
     * @throws Exception
     */
    public function getConfigurationNamesForTypeAndCountry(?string $country, string $configurationType): array
    {
        $configurationFilePaths = $this->getConfigurationFilesPathsForTypeAndCountry($country, $configurationType);
        $activeConfigurations   = $this->getActiveConfigurationFilePaths($configurationFilePaths);
        $configurationNames     = array_keys($activeConfigurations);

        return $configurationNames;
    }

    /**
     * Returns array of strings, where each string is configuration name for provided sources/type and country
     *
     * @param array       $extractionTypes
     * @param string|null $country
     *
     * @return array
     *
     * @throws Exception
     */
    public function getConfigurationNamesForTypes(?string $country, array $extractionTypes): array
    {
        $configurationNames = [];
        foreach ($extractionTypes as $extractionType) {
            $configurationNames = [
                ...$configurationNames,
                ...$this->getConfigurationNamesForTypeAndCountry($country, $extractionType),
            ];
        }

        return $configurationNames;
    }

    /**
     * @param array $configurationFilePaths
     *
     * @return array
     */
    private function getActiveConfigurationFilePaths(array $configurationFilePaths): array
    {
        $activeConfigurations = [];
        foreach ($configurationFilePaths as $configurationFilePath) {

            if (
                    !str_contains($configurationFilePath, "yaml")
                &   !str_contains($configurationFilePath, "yml")
            ) {
                continue;
            }

            $yamlFileContent = file_get_contents($configurationFilePath);
            $parsingResult   = Yaml::parse($yamlFileContent, Yaml::PARSE_CONSTANT);

            $configurationName = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, AbstractConfigurationBuilder::KEY_CONFIGURATION_NAME);
            $isEnabled         = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($parsingResult, AbstractConfigurationBuilder::KEY_CONFIGURATION_ENABLED);


            if (is_null($configurationName)) {
                throw new LogicException("Given configuration file has no name in structure. Path: {$configurationFilePath}");
            }

            if (is_null($isEnabled)) {
                throw new LogicException("Given configuration file has no information about being enabled/disabled in structure. Path: {$configurationFilePath}");
            }

            if (!$isEnabled) {
                continue;
            }

            $activeConfigurations[$configurationName] = $configurationFilePath;
        }

        return $activeConfigurations;
    }

    /**
     * Will return the configuration files paths for country, or all files paths if $country is null.
     * Returns following array structure:
     * - key is the `typ` (dom / api etc.),
     * - path is the absolute configuration file path,
     *
     * @param string|null $country
     *
     * @return array
     * @throws Exception
     */
    public function getConfigurationFilesPathsForCountry(?string $country = null): array
    {
        $typeFinder = new Finder();
        $typeFinder->directories()->in($this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . "config")
                                  ->name(self::KEY_JOB_SERVICES_CONFIGURATIONS_FILES_FOLDER_NAME);

        foreach ($typeFinder as $jobServicesSearchResult) {
            $jobServicesFolderPath = $jobServicesSearchResult;
        }

        if (empty($jobServicesFolderPath)) {
            throw new Exception("Job services folder path is not set - does the configuration folder even exist?");
        }

        $typeFinder = new Finder();
        $typeFinder->directories()->in($jobServicesFolderPath);
        $typeFinder->depth(0);

        foreach ($typeFinder as $typeDirectorySplFile) {
            if (array_key_exists($typeDirectorySplFile->getBasename(), self::$CONFIGURATION_FILES_FOR_TYPE)) {
                continue;
            }

            $countryFolderFinder = new Finder();
            $countryFolderFinder->depth(0);
            $countryFolderFinder->directories()->in($typeDirectorySplFile->getRealPath());

            foreach ($countryFolderFinder as $countryFolderSplFile) {
                $configurationFileFinder = new Finder();
                $configurationFileFinder->files()->in($countryFolderSplFile->getRealPath());

                if(
                        !is_null($country)
                    &&  $countryFolderSplFile->getBasename() !== $country
                ){
                    continue;
                }

                foreach ($configurationFileFinder as $configurationSplFile) {
                    self::$CONFIGURATION_FILES_FOR_TYPE[$typeDirectorySplFile->getBasename()][] = $configurationSplFile->getRealPath();
                }
            }

        }

        return self::$CONFIGURATION_FILES_FOR_TYPE;
    }

    /**
     * Will return array of countries for which there are some active configurations
     *
     * @return array
     * @throws Exception
     */
    public function getSupportedCountries(): array
    {
        $countryNames                      = [];
        $allConfigurationFilePaths         = [];
        $allConfigurationFilePathsForTypes = $this->getAllConfigurationFilesPaths();
        foreach ($allConfigurationFilePathsForTypes as $filesPaths) {
            $allConfigurationFilePaths = array_merge(
                $allConfigurationFilePaths,
                $filesPaths
            );
        }

        $activeConfigurations = $this->getActiveConfigurationFilePaths($allConfigurationFilePaths);
        foreach ($activeConfigurations as $activeConfigurationFilePath) {
            $countryNames[] = $this->getCountryForConfigurationPath($activeConfigurationFilePath);
        }

        return array_unique($countryNames);
    }

    /**
     * @param string $configurationFilePath
     *
     * @return string
     * @throws Exception
     */
    public function getCountryForConfigurationPath(string $configurationFilePath): string
    {
        preg_match("#" . self::CONFIGURATION_FILE_PATH_COUNTRY_NAME_REGEXP . "#", $configurationFilePath, $matches);
        $countryName = $matches['COUNTRY_NAME'];
        if (empty($countryName)) {
            throw new \Exception("Could not determine the country for the configuration file. Path: {$configurationFilePath}");
        }

        return $countryName;
    }
}