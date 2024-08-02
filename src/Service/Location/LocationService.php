<?php

namespace JobSearcher\Service\Location;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GeoTool\Service\LocationTool\LocationToolsService;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Repository\LocationRepository;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Handles providing geo-location data
 */
class LocationService
{
    /**
     * @param LocationRepository     $locationRepository
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     * @param LocationToolsService   $locationToolsService
     */
    public function __construct(
        private readonly LocationRepository     $locationRepository,
        private readonly LoggerInterface        $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly LocationToolsService   $locationToolsService
    ){}

    /**
     * Will fetch the location data for the {@see Location} and set them in database for given entity,
     * It doesn't provide any filtering by the logged-in user etc. every {@see Location} with missing data is processed
     */
    public function getForCompaniesWithoutCountry(): void
    {
        $locations = $this->locationRepository->findAllWithoutCountry();
        foreach ($locations as $location) {
            $location = $this->getDataForLocation($location, false);
            if (!empty($location)) {
                $this->entityManager->persist($location);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Will provide and set geolocation data for the {@see Location}, saves the data on entity in database
     * if {@see Location} got all the necessary data set then it's skipped
     *
     * @param Location $location
     * @param bool     $skipExisting - if existing entity in DB is found, then if this is set to TRUE the found location will be just returned
     *                                 else if this property is false then even if location was found in DB it will be updated
     *                                 if some of it fields are missing
     *
     * @return Location|null - the location that was handled
     *                         - if provided location already exists in DB then the one from DB is returned (after update),
     *                         - if that's new location then it will be returned (after update),
     *                         - if something goes wrong in the process `null` is returned,
     */
    public function getDataForLocation(Location $location, bool $skipExisting = true): ?Location
    {
        $this->logger->info("Now getting data of location with id: {$location->getId()}");

        try {
            $foundEntity = $this->locationRepository->findFirstExisting(
                $location->getName(),
                $location->getCountry(),
                $location->getLongitude(),
                $location->getLatitude()
            );

            if (
                (
                        $skipExisting
                    &&  !empty($foundEntity)
                )
                ||  $foundEntity?->hasAllBaseInformation() // no need to do anything with already full entity
            ) {
                return $foundEntity;
            }

            $locationData = $this->findOneByLocationName($location->getName());
            if (empty($locationData)) {
                $this->logger->warning("Could not fetch any location data for location named: {$location->getName()}");
                return $location;
            }

            $location->setCountry($locationData->getCountry());
            $location->setLatitude($locationData->getLatitude());
            $location->setLongitude($locationData->getLongitude());
            $location->setCountryCode($locationData->getCountryCode());

            return $location;
        }catch(Exception | TypeError $e){
            $this->logger->critical("Exception was thrown while fetching and setting the company location data, skipping", [
                "location" => [
                    "id" => $location->getId(),
                ],
                "class"     => self::class,
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);
            return null;
        }

    }

    /**
     * Will first try to find the location data in database,
     * if none is found only then will call API to fetch data,
     * - after fetching it, the data will be saved in DB for future calls
     *
     * @param string $locationName
     *
     * @return Location|null
     */
    public function findOneByLocationName(string $locationName): ?Location
    {
        if (empty($locationName)) {
            return null;
        }

        $location = $this->locationRepository->findFirstExisting($locationName);
        if (empty($location)) {

            /**
             * Location finding is disabled for same reason as {@see OfferExtractionService::handleLocationDistance}
             */
            return null;

            $location = $this->locationToolsService->getLocationData($locationName);
            if (empty($location)) {
                return null;
            }

            /**
             * Because it's possible that different search string will yield the same city name and this will lead
             * to violation the unique constraint
             */
            $existingEntity = $this->locationRepository->findFirstExisting($location->getLocationName());
            if (empty($existingEntity)) {
                $this->entityManager->persist($location);
                $this->entityManager->flush();
            }
        }

        return $location;
    }

}