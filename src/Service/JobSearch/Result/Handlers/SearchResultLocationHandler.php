<?php

namespace JobSearcher\Service\JobSearch\Result\Handlers;

use JobSearcher\DTO\JobService\SearchResult\SearchResultDto;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Repository\LocationRepository;

/**
 * Provides logic for setting the {@see Location} on the {@see JobSearchResult} based on the {@see SearchResultDto}
 */
class SearchResultLocationHandler
{

    public function __construct(
        private readonly LocationRepository $locationRepository
    ){}

    /**
     * Will set all {@see Location} entities of the {@see JobSearchResult} based on the {@see SearchResultDto}
     */
    public function setEntityLocationsFromSearch(JobSearchResult $offerEntity, SearchResultDto $searchResult): JobSearchResult
    {
        $specialLetters      = "äöüÄÖÜß";
        $regexpIsSplitAble   = "/^([{$specialLetters}\w+-]*(?<SPLIT_CHARACTERS>[\/,]){1}(?<SPACING>[ ]{0,}))*/";
        $splitAbleCharacters = ["/,"];

        foreach ($searchResult->getCompanyDetailDto()->getCompanyLocations() as $companyLocation) {
            if (preg_match($regexpIsSplitAble, $companyLocation)) {
                $companyLocation = str_replace($splitAbleCharacters, ",", $companyLocation);
                $subLocations    = explode(",", $companyLocation);

                foreach ($subLocations as $subLocation) {
                    $this->setOneLocationFromSearch($subLocation, $offerEntity, $searchResult);
                }

                continue;
            }

            $this->setOneLocationFromSearch($companyLocation, $offerEntity, $searchResult);
        }

        return $offerEntity;
    }

    /**
     * Will set single {@see Location} of the {@see JobSearchResult} based on the {@see SearchResultDto}
     * Nothing is returned because the object prop is getting modified
     *
     * @param string          $companyLocation
     * @param JobSearchResult $offerEntity
     * @param SearchResultDto $searchResult
     */
    private function setOneLocationFromSearch(
        string          $companyLocation,
        JobSearchResult $offerEntity,
        SearchResultDto $searchResult
    ): void {

        $usedLocationEntity = $this->locationRepository->findFirstExisting(
            $companyLocation,
            $searchResult->getCompanyCountry()
        );

        if (empty($usedLocationEntity)) {
            $usedLocationEntity = new Location($companyLocation);
        }

        if (empty($usedLocationEntity->getCountry())) {
            $usedLocationEntity->setCountry($searchResult->getCompanyCountry());
        }


        $offerEntity->addLocation($usedLocationEntity);
    }
}