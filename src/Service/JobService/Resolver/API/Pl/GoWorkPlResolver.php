<?php

namespace JobSearcher\Service\JobService\Resolver\API\Pl;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\DetailPageAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationDistanceAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use Symfony\Component\DomCrawler\Crawler;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;

/**
 * Handles resolving data for service {@link https://www.gowork.pl/}, yaml file: `gowork.pl.yaml`
 */
class GoWorkPlResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;
    use SearchUriAwareTrait;
    use LocationDistanceAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;
    use DetailPageAwareTrait;

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        $baseSearchUriDto = $this->getBaseSearchUriConfigurationDto($parameters);
        $gluedKeywords    = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriDto->isEncodeQuery(),
            $baseSearchUriDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $searchUri = $this->getSearchUri($parameters)
                     . $gluedKeywords
                     . ";st/";

        $locationName     = $this->getLocationName($parameters);
        $locationDistance = $this->getLocationDistance($parameters);
        $allowedDistances = $baseSearchUriDto->getLocationDistanceConfiguration()->getAllowedDistances();

        $usedDistance = null;
        if (!is_null($locationDistance)) {
            $usedDistance = ArrayTypeProcessor::getKeyForClosestNumber($locationDistance, $allowedDistances);
        }

        if (!empty($locationName)) {
            $searchUri .= $locationName . ";l/";
        }

        if (!empty($locationDistance)) {
            $searchUri .= $usedDistance . ";km/";
        }

        $searchUri .= $this->getPageNumber($parameters) . ";pg/";

        return $searchUri;
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function buildDetailPageDataArray(array $parameters): array
    {
        $calledUri      = $this->getDetailPageUrl($parameters);
        $crawlerService = $this->kernel->getContainer()->get(CrawlerService::class);
        $crawlerConfig  = new CrawlerConfigurationDto($calledUri, CrawlerService::CRAWLER_ENGINE_GOUTTE);
        $crawler        = $crawlerService->crawl($crawlerConfig);

        $descriptionSelectors = [
            '.job-content__wrapper',
            '.offer-content .desc',
            '.offer-content .imported-offer-content',
            '.offer-content .ogl__description',
            '.g-job-offer-details-template .g-job-offer-details-template__section-wrapper:nth-of-type(3)',
        ];

        $descriptionContent = "";
        foreach ($descriptionSelectors as $selector) {
            foreach ($crawler->filter($selector) as $node) {
                $descriptionContent = (new Crawler($node))->html();
                if (!empty($descriptionContent)) {
                    break 2;
                }
            }
        }

        $dataArray = [
            "detail" => [
                'description' => $descriptionContent,
            ]
        ];

        return $dataArray;
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        // TODO: Implement init() method.
    }
}