<?php

namespace JobSearcher\Service\JobService\Resolver\API\Factory;

use Exception;
use JobSearcher\Constants\JobOfferService\GermanJobOfferService;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto;
use JobSearcher\Service\JobService\ConfigurationBuilder\ConfigurationBuilderInterface;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use LogicException;

/**
 * Handles resolving the job offer "detail page url" per job service configuration
 */
class JobOfferDetailPageUrlResolverFactory
{
    /**
     * Handles providing the job url based on the set of rules per configuration
     *
     * @param array                $offerInformationArrays
     * @param MainConfigurationDto $mainConfigurationDto
     *
     * @return string|null
     * @throws Exception
     */
    public static function resolveForConfigurationName(array $offerInformationArrays, MainConfigurationDto $mainConfigurationDto): ?string
    {
        switch ($mainConfigurationDto->getConfigurationName()) {
            case GermanJobOfferService::TIDERI_DE:
                $offerId = self::resolveJobId($offerInformationArrays, $mainConfigurationDto);
                return $mainConfigurationDto->getHost() . "/stellenangebot/" . $offerId;

            default:
                $message = "Data returned from API for this configuration {$mainConfigurationDto->getConfigurationName()} "
                           . " is missing the job offer detail page url. There was an attempt to extract the url using given "
                           . "key(s) {$mainConfigurationDto->getJsonStructureConfigurationDto()->getJobOfferUrl()}. "
                           . "Since it could not be extracted it defaults to using the: " . self::class
                           . ", yet no switch::case was provided for this configuration - please add it";
                throw new LogicException($message);
        }
    }

    /**
     * Will yield the job offer id / identifier / slug
     *
     * @param array $offerInformationArrays - array containing arrays which contain:
     *                                        - data from pagination page,
     *                                        - data from detail page,
     * @param MainConfigurationDto $mainConfigurationDto
     *
     * @return string|null
     * @throws Exception
     */
    private static function resolveJobId(array $offerInformationArrays, MainConfigurationDto $mainConfigurationDto): ?string
    {
        $jobOfferId = null;
        foreach ($offerInformationArrays as $offerInformation) {
            try{
                ArrayTypeProcessor::checkArrayKeyIsSetByDottedString(
                    $offerInformation,
                    $mainConfigurationDto->getJsonStructureConfigurationDto()->getDetailPageIdentifierField(),
                );

                $jobOfferId = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString(
                    $offerInformation,
                    $mainConfigurationDto->getJsonStructureConfigurationDto()->getDetailPageIdentifierField(),
                );
            } catch (Exception) {
                continue;
            }
        }

        return $jobOfferId;
    }
}