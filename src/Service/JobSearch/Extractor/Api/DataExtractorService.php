<?php

namespace JobSearcher\Service\JobSearch\Extractor\Api;

use Exception;
use JobSearcher\Service\JobService\ConfigurationBuilder\Api\ApiConfigurationBuilderInterface;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;

/**
 * Provides general logic for data extractions
 */
class DataExtractorService
{
    /**
     * Will attempt to extract given json structure from one of the provided arrays
     * This is necessary as some json structure keys might be present in the job detail while other
     * in pagination data array
     *
     * @param array       $dataArrays
     * @param string|null $jsonStructureString
     *
     * @return mixed
     * @throws Exception
     */
    public static function extractDataFromOneOfDataArrays(array $dataArrays, ?string $jsonStructureString): mixed
    {
        // it might be that given data is not present in the json structure
        if( empty($jsonStructureString) ){
            return null;
        }

        $jsonKeys   = explode(ApiConfigurationBuilderInterface::JSON_OR_SEPARATOR, $jsonStructureString);
        $foundValue = null;
        foreach ($dataArrays as $dataArray) {
            foreach ($jsonKeys as $jsonKey) {
                try{
                    ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($dataArray, $jsonKey);
                }catch(Exception){
                    continue;
                }

                $foundValue = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($dataArray, $jsonKey);
                if (!empty($foundValue)) {
                    break 2;
                }
            }
        }

        return $foundValue;
    }

}