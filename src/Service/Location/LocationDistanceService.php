<?php

namespace JobSearcher\Service\Location;

use Doctrine\ORM\EntityManagerInterface;
use GeoTool\Service\DistanceTool\DistanceToolInterface;
use GeoTool\Service\DistanceTool\DistanceToolService;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Entity\Location\LocationDistance;
use JobSearcher\Repository\Location\LocationDistanceRepository;
use JobSearcher\Repository\LocationRepository;
use Psr\Log\LoggerInterface;

/**
 * Handles the {@see LocationDistance}
 */
class LocationDistanceService
{
    public function __construct(
        private readonly LocationDistanceRepository $locationDistanceRepository,
        private readonly LocationRepository         $locationRepository,
        private readonly DistanceToolService        $distanceToolService,
        private readonly CoordinateService          $coordinateService,
        private readonly LoggerInterface            $logger,
        private readonly EntityManagerInterface     $entityManager
    ){}

    /**
     * Get distance between 2 locations
     *
     * @param string $startingPlace
     * @param string $endingPlace
     *
     * @return float|null
     */
    public function getDistance(string $startingPlace, string $endingPlace): ?float
    {
        // try to get the coordinates from DB first, call API only if no coordinate data is present in DB
        $coordinateDtos     = $this->coordinateService->findCoordinates([$startingPlace, $endingPlace]);
        $numericIndexedDtos = [];
        $missingLocations   = [];

        if (empty($coordinateDtos)) {
            $locationNameString = json_encode($missingLocations);
            $message = "
                Cannot find distance between give locations names: {$startingPlace} and {$endingPlace}.
                Because it's impossible to find location data for missing locations: {$locationNameString}
            ";
            $this->logger->warning($message);
            return null;
        }

        foreach ($coordinateDtos as $dto) {
            $numericIndexedDtos[] = $dto;
        }

        $firstCoordinateDto  = $numericIndexedDtos[0];
        $secondCoordinateDto = $numericIndexedDtos[1];

        $firstLocation  = $this->locationRepository->find($firstCoordinateDto->getRelatedEntityId());
        $secondLocation = $this->locationRepository->find($secondCoordinateDto->getRelatedEntityId());

        $distanceEntity = $this->findPredictedDistanceForSimilarRecords(
            $firstLocation,
            $secondLocation,
            DistanceToolInterface::DISTANCE_GEO_LOCATION_SIMILARITY_PERCENTAGE
        );

        if (!empty($distanceEntity)) {
            return $distanceEntity->getDistance();
        }

        $distanceFromApi = $this->distanceToolService->getDistance($coordinateDtos);
        if (empty($distanceFromApi)) {
            return null;
        }

        $locationDistance = new LocationDistance();
        $locationDistance->setFirstLocation($firstLocation);
        $locationDistance->setSecondLocation($secondLocation);
        $locationDistance->setDistance($distanceFromApi);

        $this->entityManager->persist($locationDistance);
        $this->entityManager->flush();

        return $this->distanceToolService->getDistance($coordinateDtos);
    }

    /**
     * Will return one {@see LocationDistance} for provided coordinates,
     * - either it will return one existing entity (highly doubted, as coordinates would need to be exact the same),
     * - or will return the similar / predicted distance, based on already existing records
     *
     * @param Location $firstLocation
     * @param Location $secondLocation
     * @param int $similarityPercentage
     *
     * @return LocationDistance|null
     */
    public function findPredictedDistanceForSimilarRecords(
        Location $firstLocation,
        Location $secondLocation,
        int      $similarityPercentage
    ): ?LocationDistance
    {
        $locationDistanceEntities = $this->locationDistanceRepository->findPredictedDistanceForSimilarRecords(
            $firstLocation,
            $secondLocation,
            $similarityPercentage
        );

        if (empty($locationDistanceEntities)) {
            return null;
        }

        foreach($locationDistanceEntities as $locationDistanceEntity){
            // exact the same entity, not comparing objects to avoid id/date comparison etc.
            if(
                    $locationDistanceEntity->getFirstLocation()->getLatitude() === $firstLocation->getLatitude()
                &&  $locationDistanceEntity->getFirstLocation()->getLongitude() === $firstLocation->getLongitude()
                &&  $locationDistanceEntity->getSecondLocation()->getLatitude() === $secondLocation->getLatitude()
                &&  $locationDistanceEntity->getSecondLocation()->getLongitude() === $secondLocation->getLongitude()
            ){
                return $locationDistanceEntity;
            }
        }

        $newDistance = $this->calculateNewSimilarityDistance($firstLocation, $secondLocation, $locationDistanceEntities);

        /**
         * Non persisted object with calculated similarity, not persisting to avoid having tones of similarities in DB
         */
        $returnedLocationDistance = new LocationDistance();
        $returnedLocationDistance->setFirstLocation($firstLocation);
        $returnedLocationDistance->setSecondLocation($secondLocation);
        $returnedLocationDistance->setDistance($newDistance);

        return $returnedLocationDistance;
    }

    /**
     * Takes all found existing location distances, iterates over them and returns new avg. similar distance
     *
     * @param Location $firstLocation
     * @param Location $secondLocation
     * @param LocationDistance[] $existingLocationDistances
     * @return float
     */
    private function calculateNewSimilarityDistance(
        Location $firstLocation,
        Location $secondLocation,
        array    $existingLocationDistances
    ): float
    {
        $distanceBetweenProvidedLocations = $this->distanceToolService::calculateDistanceBetweenTwoPoints(
            $firstLocation->getLatitude(),
            $firstLocation->getLongitude(),
            $secondLocation->getLatitude(),
            $secondLocation->getLongitude()
        );

        $summaryNewDistances = 0;
        foreach ($existingLocationDistances as $existingLocationDistance) {
            $distanceBetweenFoundLocations = $this->distanceToolService::calculateDistanceBetweenTwoPoints(
                $existingLocationDistance->getFirstLocation()->getLatitude(),
                $existingLocationDistance->getFirstLocation()->getLongitude(),
                $existingLocationDistance->getSecondLocation()->getLatitude(),
                $existingLocationDistance->getSecondLocation()->getLongitude()
            );

            /**
             * Not taking the percentage from function parameter as it's only for finding similar distance entity
             * Now once the entity is found the percentage is calculated again to get the real percentage difference
             * as it might be smaller
             */
            $dividedValue = $distanceBetweenProvidedLocations;
            $divider      = $distanceBetweenFoundLocations;
            if($divider < $dividedValue){
                $dividedValue = $distanceBetweenFoundLocations;
                $divider      = $distanceBetweenProvidedLocations;
            }
            $distancesPercentageDifference = (1 - $dividedValue / $divider) * 100;
            $isIncreased                   = $distanceBetweenProvidedLocations > $distanceBetweenFoundLocations;
            $distanceOffset                = $existingLocationDistance->getDistance() * $distancesPercentageDifference / 100;
            $newDistance                   = $existingLocationDistance->getDistance() - $distanceOffset;
            if ($isIncreased) {
                $newDistance = $existingLocationDistance->getDistance() + $distanceOffset;
            }

            $summaryNewDistances += $newDistance;
        }

        $avgDistance = round($summaryNewDistances / count($existingLocationDistances), 2);
        return $avgDistance;
    }
}