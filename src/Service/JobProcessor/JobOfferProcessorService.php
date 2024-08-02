<?php

namespace JobSearcher\Service\JobProcessor;

use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Service\DOM\DomTagWrapperService;

/**
 * Performs variety of offer processors before it's returned / ready to be applied to / shown
 */
class JobOfferProcessorService
{

    /**
     * @var array $tagsWithColors
     */
    private array $tagsWithColors = [];

    /**
     * @return array
     */
    public function getTagsWithColors(): array
    {
        return $this->tagsWithColors;
    }

    /**
     * @param array $tagsWithColors
     */
    public function setTagsWithColors(array $tagsWithColors): void
    {
        $this->tagsWithColors = $tagsWithColors;
    }

    /**
     * Performs set of processing on the search results
     *
     * @param SearchResultDto[] $searchResultDtos
     *
     * @return SearchResultDto[]
     */
    public function processSearchResults(array $searchResultDtos): array
    {
        foreach($searchResultDtos as $searchResultDto){
            $this->processSearchResult($searchResultDto);
        }

        return $searchResultDtos;
    }

    /**
     * Will process single {@see SearchResultDto}. Returns the {@see SearchResultDto} with its values / data being processed.
     * For example:
     * - will wrap tags into custom `color based class names` (different from the keyword class names),
     *
     * Not returning anything as the values are changed via setters, so the original object is getting modified
     *
     * @param SearchResultDto $searchResultDto
     */
    public function processSearchResult(SearchResultDto $searchResultDto): void
    {
        $offerDescriptionWithColorClasses = DomTagWrapperService::wrapTagsIntoColorClass(
            $searchResultDto->getJobDetailDto()->getJobDescription(),
            $this->getTagsWithColors()
        );

        $searchResultDto->getJobDetailDto()->setJobDescription($offerDescriptionWithColorClasses);
    }

}