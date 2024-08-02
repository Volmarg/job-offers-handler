<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\DomHtml;

use Exception;
use JobSearcher\DTO\JobSearch\DOM\DomElementConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseMainConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\Crawler\CrawlerConfigurationDto;
use JobSearcher\Exception\Configuration\DomHtml\ConfigurationNotFoundException;

/**
 * Main dto with data that is used to search job offers
 */
class MainConfigurationDto extends BaseMainConfigurationDto
{
    /**
     * @var SearchUriConfigurationDto $searchUriConfigurationDto
     */
    private SearchUriConfigurationDto $searchUriConfigurationDto;

    /**
     * @var CrawlerConfigurationDto $crawlerConfigurationDto
     */
    private CrawlerConfigurationDto $crawlerConfigurationDto;

    /**
     * @var DetailPageConfigurationDto $detailPageConfigurationDto
     */
    private DetailPageConfigurationDto $detailPageConfigurationDto;

    /**
     * @var DomElementConfigurationDto[] $domElementsSelectorsAndAttributeConfigurations
     */
    private array $domElementsSelectorsAndAttributeConfigurations = [];

    /**
     * @return SearchUriConfigurationDto
     */
    public function getSearchUriConfigurationDto(): SearchUriConfigurationDto
    {
        return $this->searchUriConfigurationDto;
    }

    /**
     * @param SearchUriConfigurationDto $searchUriConfigurationDto
     */
    public function setSearchUriConfigurationDto(SearchUriConfigurationDto $searchUriConfigurationDto): void
    {
        $this->searchUriConfigurationDto = $searchUriConfigurationDto;
    }

    /**
     * @return CrawlerConfigurationDto
     */
    public function getCrawlerConfigurationDto(): CrawlerConfigurationDto
    {
        return $this->crawlerConfigurationDto;
    }

    /**
     * @param CrawlerConfigurationDto $crawlerConfigurationDto
     */
    public function setCrawlerConfigurationDto(CrawlerConfigurationDto $crawlerConfigurationDto): void
    {
        $this->crawlerConfigurationDto = $crawlerConfigurationDto;
    }

    /**
     * @param array $domElementsSelectorsAndAttributeConfigurations
     */
    public function setDomElementsSelectorsAndAttributeConfigurations(array $domElementsSelectorsAndAttributeConfigurations): void
    {
        $this->domElementsSelectorsAndAttributeConfigurations = $domElementsSelectorsAndAttributeConfigurations;
    }

    /**
     * @return DetailPageConfigurationDto
     */
    public function getDetailPageConfigurationDto(): DetailPageConfigurationDto
    {
        return $this->detailPageConfigurationDto;
    }

    /**
     * @param DetailPageConfigurationDto $detailPageConfigurationDto
     */
    public function setDetailPageConfigurationDto(DetailPageConfigurationDto $detailPageConfigurationDto): void
    {
        $this->detailPageConfigurationDto = $detailPageConfigurationDto;
    }

    /**
     * @param string $purposeName
     * @return DomElementConfigurationDto
     * @throws Exception
     */
    public function getDomElementSelectorAndAttributeConfiguration(string $purposeName): DomElementConfigurationDto
    {
        $foundDto = null;
        foreach($this->domElementsSelectorsAndAttributeConfigurations as $domElementsSelectorsAndAttributeConfiguration){
            if($domElementsSelectorsAndAttributeConfiguration->getDomElementPurpose() === $purposeName){
                $foundDto = $domElementsSelectorsAndAttributeConfiguration;
                break;
            }
        }

        if( empty($foundDto) ){
            throw new ConfigurationNotFoundException("No " . DomElementConfigurationDto::class . " was found for given purpose name : {$purposeName}");
        }

        return $foundDto;
    }

    /**
     * Will check if there is dom selector present for provided purpose name
     * @param string $purposeName
     * @return bool
     */
    public function hasDomSelectorForPurpose(string $purposeName): bool
    {
        try{
            $this->getDomElementSelectorAndAttributeConfiguration($purposeName);
        }catch(Exception $e){
            return false;
        }

        return true;
    }

}