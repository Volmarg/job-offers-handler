<?php

namespace JobSearcher\Service\JobSearch\UrlHandler\Api;

use Exception;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\JsonStructureConfigurationDto;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use JobSearcher\Service\Url\UrlService;
use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto;
use JobSearcher\Service\JobSearch\UrlHandler\AbstractUrlHandlerService;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles any kind of logic related to building full crawl-able links, pagination urls etc
 */
class UrlHandlerService extends AbstractUrlHandlerService
{

    /**
     * @var MainConfigurationDto $mainConfigurationDto
     */
    protected MainConfigurationDto $mainConfigurationDto;

    /**
     * @param MainConfigurationDto $mainConfigurationDto
     * @param KernelInterface      $kernel
     */
    public function __construct(
        MainConfigurationDto $mainConfigurationDto,
        KernelInterface      $kernel
    )
    {
        $this->mainConfigurationDto = $mainConfigurationDto;

        parent::__construct(
            $mainConfigurationDto,
            $mainConfigurationDto->getDetailPageConfigurationDto(),
            $mainConfigurationDto->getSearchUriConfigurationDto(),
            $kernel
        );
    }

    /**
     * Will return absolute url for detail page calls
     *
     * @param array $jobInformationFromPagination
     * @return string
     * @throws Exception
     */
    public function buildAbsoluteUrlToDetailPage(array $jobInformationFromPagination): string
    {
        try{
            $offerUriJsonKey = $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobOfferUrl();
            if (empty($offerUriJsonKey)) {
                $url = $this->glueOfferUrl($jobInformationFromPagination);
                return $url;
            }

            ArrayTypeProcessor::checkArrayKeyIsSetByDottedString($jobInformationFromPagination, $offerUriJsonKey);
            $url = $this->buildFromDirectOfferUrl($jobInformationFromPagination);
            if (!empty($url)) {
                return $url;
            }
        }catch(Exception){
            // nothing on purpose
        }

        $url = $this->glueOfferUrl($jobInformationFromPagination);
        return $url;
    }

    /**
     * Will try to obtain the detail page url by using the {@see JsonStructureConfigurationDto::getJobOfferUrl()}
     *
     * @param array $jobInformationFromPagination
     *
     * @return string
     */
    private function buildFromDirectOfferUrl(array $jobInformationFromPagination): string
    {
        $url = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($jobInformationFromPagination, $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getJobOfferUrl());
        if (!str_contains($url, $this->mainConfigurationDto->getDetailPageConfigurationDto()->getBaseHost())) {
            return $this->mainConfigurationDto->getDetailPageConfigurationDto()->getBaseHost() . $url;
        }
        return $url;
    }

    /**
     * Will try to glue the url from segments
     *
     * @param array $jobInformationFromPagination
     *
     * @return string
     */
    private function glueOfferUrl(array $jobInformationFromPagination): string
    {
        $baseUri = $this->mainConfigurationDto->getDetailPageConfigurationDto()->getBaseUri();
        if ($this->mainConfigurationDto->getDetailPageConfigurationDto()->isIdentifierPlacementRawBody()) {
            return $this->buildAbsoluteUrl($baseUri, self::URL_TYPE_DETAIL_PAGE);
        }

        $detailPageIdentifierField = $this->mainConfigurationDto->getJsonStructureConfigurationDto()->getDetailPageIdentifierField();

        // Can either be for example numeric `id` field or any kind of `slug` etc.
        $identifier = ArrayTypeProcessor::getDataFromArrayByDotSeparatedString($jobInformationFromPagination, $detailPageIdentifierField);
        $calledUri  = $baseUri;
        if(
            !UrlService::hasUriTrailingSlash($baseUri)
            &&  $this->mainConfigurationDto->getDetailPageConfigurationDto()->isIdentifierAfterSlash()
        ){
            $calledUri .= DIRECTORY_SEPARATOR;
        }

        $calledUri .= $identifier;
        return $this->buildAbsoluteUrl($calledUri, self::URL_TYPE_DETAIL_PAGE);
    }
}