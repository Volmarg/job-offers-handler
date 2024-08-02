<?php

namespace JobSearcher\Service\JobSearch\Extractor\Api;

use Exception;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\JsonStructureConfigurationDto;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use TypeError;

/**
 * Handles location related extractions
 */
class LocationExtractorService
{
    /**
     * Will extract the locations from the provided data array
     *
     * @param array                         $jobOfferInformationDataArrays
     * @param JsonStructureConfigurationDto $jsonStructureConfigurationDto
     *
     * @return array
     * @throws Exception
     */
    public function extractLocationFromDataArrays(array $jobOfferInformationDataArrays, JsonStructureConfigurationDto $jsonStructureConfigurationDto): array
    {
        $locationNames = [];
        if ($jsonStructureConfigurationDto->isLocationTypeSinglePath()) {
            return $this->handleSinglePathLocation($jobOfferInformationDataArrays, $jsonStructureConfigurationDto);
        }

        if ($jsonStructureConfigurationDto->isLocationTypeArray()) {
            return $this->handleMultipleLocations($jobOfferInformationDataArrays, $jsonStructureConfigurationDto);
        }

        return $locationNames;
    }

    /**
     * @param array                         $jobOfferInformationDataArrays
     * @param JsonStructureConfigurationDto $jsonStructureConfigurationDto
     *
     * @return array
     * @throws Exception
     */
    private function handleSinglePathLocation(array $jobOfferInformationDataArrays, JsonStructureConfigurationDto $jsonStructureConfigurationDto): array
    {
        $location = DataExtractorService::extractDataFromOneOfDataArrays($jobOfferInformationDataArrays, $jsonStructureConfigurationDto->getLocationSingleEntryPath());
        if( empty($location) ){

            return [];
        }elseif( is_string($location) ){

            $locations = array_unique(explode("," , $location));
            return $locations;
        }elseif( is_array($location) ){
            $locationString = implode(", " , array_filter($location));
            return [$locationString];
        }else{

            throw new Exception("Unsupported location variable type, got: " . gettype($location));
        }
    }

    /**
     * @param array                         $jobOfferInformationDataArrays
     * @param JsonStructureConfigurationDto $jsonStructureConfigurationDto
     *
     * @return array
     * @throws Exception
     */
    private function handleMultipleLocations(array $jobOfferInformationDataArrays, JsonStructureConfigurationDto $jsonStructureConfigurationDto): array
    {
        $locationDataArrays = DataExtractorService::extractDataFromOneOfDataArrays($jobOfferInformationDataArrays, $jsonStructureConfigurationDto->getLocationArrayStructurePath());
        if( empty($locationDataArrays) ){
            return [];
        }

        $locationNames = [];
        foreach($locationDataArrays as $locationDataArray){
            $locationName = null;
            /**
             * This is necessary to cover cases where location data array has:
             * - nested sub-arrays or just "key->value"
             * If extraction from sub arrays fails then it will try with "key=>value"
             */
            try{
                $locationName = DataExtractorService::extractDataFromOneOfDataArrays($locationDataArray, $jsonStructureConfigurationDto->getLocationSingleEntryPath());
            }catch(Exception|TypeError){
                // do nothing
            }

            try{
                if( empty($locationName) ){
                    ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($locationDataArray, $jsonStructureConfigurationDto->getLocationSingleEntryPath());
                    $locationName = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($locationDataArray, $jsonStructureConfigurationDto->getLocationSingleEntryPath());
                }
            }catch(Exception|TypeError){
                continue;
            }

            $locationNames[] = $locationName;
        }

        return $locationNames;
    }
}