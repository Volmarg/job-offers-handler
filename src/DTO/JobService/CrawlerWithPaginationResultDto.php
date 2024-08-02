<?php

namespace JobSearcher\DTO\JobService;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\BasePaginationOfferDto;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerWithPaginationResultDto
{
    public function __construct(
        private Crawler                $crawler,
        private BasePaginationOfferDto $paginationOfferDto
    ){}

    /**
     * @return Crawler
     */
    public function getCrawler(): Crawler
    {
        return $this->crawler;
    }

    /**
     * @return BasePaginationOfferDto
     */
    public function getPaginationOfferDto(): BasePaginationOfferDto
    {
        return $this->paginationOfferDto;
    }

}