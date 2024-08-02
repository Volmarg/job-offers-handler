<?php

namespace JobSearcher\Service\Location;

use Doctrine\ORM\EntityManagerInterface;
use GeoTool\Dto\CoordinateTool\CoordinateDto;
use GeoTool\GeoToolBundle;
use GeoTool\Service\CoordinateTool\CoordinateToolService;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Repository\LocationRepository;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * This service is related to {@see GeoToolBundle}
 */
class CoordinateService
{
    public function __construct(
        private readonly LocationRepository     $locationRepository,
        private readonly CoordinateToolService  $coordinateToolService,
        private readonly LoggerInterface        $logger,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Will try to obtain the coordinates data both from db and api:
     * - if locations data is missing in DB then it falls-back to api
     *
     * @param array $locationNames
     * @return array
     */
    public function findCoordinates(array $locationNames): array
    {
        // try to get the coordinates from DB first, call API only if no coordinate data is present in DB
        $coordinateDtosFromDb = $this->findCoordinatesInDb($locationNames);
        $coordinateDtoCount   = count($coordinateDtosFromDb);
        if ($coordinateDtoCount !== 2) {
            throw new LogicException("Expected 2 entries in coordinate array, got: {$coordinateDtoCount}");
        }

        $allCoordinateDtos = $coordinateDtosFromDb;

        foreach ($coordinateDtosFromDb as $locationName => $dto) {
            if (empty($dto)) {
                $missingLocations[] = $locationName;
            }
        }

        if (!empty($missingLocations)) {
            $coordinateDtosFromApi = $this->findCoordinatesInApi($missingLocations);
            foreach ($coordinateDtosFromApi as $foundCoordinateDto) {
                $location = new Location();
                $location->setName($foundCoordinateDto->getLocationName());
                $location->setLatitude($foundCoordinateDto->getLatitude());
                $location->setLongitude($foundCoordinateDto->getLongitude());

                $this->entityManager->persist($location);
                $this->entityManager->flush();

                $foundCoordinateDto->setRelatedEntityId($location->getId());

                $allCoordinateDtos[$foundCoordinateDto->getLocationName()] = $foundCoordinateDto;
            }
        }

        return $allCoordinateDtos;
    }

    /**
     * Returns Array of {@see CoordinateDto} where:
     * - key is the searched location name,
     * - value is the {@see CoordinateDto} or {@see null} if no coordinate was found
     *
     * @param $locationNames Array<string>
     *
     * @return CoordinateDto[]
     */
    private function findCoordinatesInDb(array $locationNames): array
    {
        $locationDtos = [];
        foreach ($locationNames as $locationName) {
            $location = $this->locationRepository->findFirstExisting($locationName);
            if (
                    empty($location)
                ||  empty($location->getLongitude())
                ||  empty($location->getLatitude())
            ) {
                $locationDtos[$locationName] = null;
                continue;
            }

            $coordinateDto = new CoordinateDto();
            $coordinateDto->setLocationName($location->getName());
            $coordinateDto->setLongitude($location->getLongitude());
            $coordinateDto->setLatitude($location->getLatitude());
            $coordinateDto->setRelatedEntityId($location->getId());

            $locationDtos[$locationName] = $coordinateDto;
        }

        return $locationDtos;
    }

    /**
     * Will try to obtain the coordinate data by calling the geo-tool
     *
     * @param array $locationNames
     * @return Array<null, CoordinateDto>
     */
    private function findCoordinatesInApi(array $locationNames): array
    {
        $foundCoordinateDtos = $this->coordinateToolService->getCoordinates($locationNames);
        if (empty($foundCoordinateDtos)) {
            $locationNameString = json_encode($locationNames);
            $message = "
               Impossible to find location data for locations: {$locationNameString}
            ";
            $this->logger->warning($message);
            return [];
        }

        return $foundCoordinateDtos;
    }
}