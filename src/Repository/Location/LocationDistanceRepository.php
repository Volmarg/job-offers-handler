<?php

namespace JobSearcher\Repository\Location;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Entity\Location\LocationDistance;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;

/**
 * @method LocationDistance|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocationDistance|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocationDistance[]    findAll()
 * @method LocationDistance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationDistanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocationDistance::class);
    }

    /**
     * Will save single entity
     *
     * @param LocationDistance $locationDistance
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(LocationDistance $locationDistance): void
    {
        $this->_em->persist($locationDistance);
        $this->_em->flush();
    }

    /**
     * Will return one {@see LocationDistance} entities for provided coordinates,
     * - either it will return existing entity (highly doubted, as coordinates would need to be exact the same),
     * - or will return the similar / predicted distance, based on already existing records
     *
     * @param Location $firstLocation
     * @param Location $secondLocation
     * @param int      $similarityPercentage
     *
     * @return LocationDistance[]
     */
    public function findPredictedDistanceForSimilarRecords(
        Location $firstLocation,
        Location $secondLocation,
        int      $similarityPercentage
    ): array
    {
        $queryParams = $this->buildPredictedDistanceQueryParams($firstLocation, $secondLocation, $similarityPercentage);

        // query
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("ld")
            ->from(LocationDistance::class, "ld")
            ->join(Location::class, "fl", Join::WITH, "fl.id = ld.firstLocation")
            ->join(Location::class, "sl", Join::WITH, "sl.id = ld.secondLocation")
            ->where(
                $queryBuilder->expr()->orX(
                    // first coordinate is FL
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->between("fl.longitude", ':firstCoordinateMinLongitude', ':firstCoordinateMaxLongitude'),
                        $queryBuilder->expr()->between("fl.latitude", ':firstCoordinateMinLatitude', ':firstCoordinateMaxLatitude'),

                        $queryBuilder->expr()->between("sl.longitude", ':secondCoordinateMinLongitude', ':secondCoordinateMaxLongitude'),
                        $queryBuilder->expr()->between("sl.latitude", ':secondCoordinateMinLatitude', ':secondCoordinateMaxLatitude'),
                    ),

                    // first coordinate is SL (reversed)
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->between("sl.longitude", ':firstCoordinateMinLongitude', ':firstCoordinateMaxLongitude'),
                        $queryBuilder->expr()->between("sl.latitude", ':firstCoordinateMinLatitude', ':firstCoordinateMaxLatitude'),

                        $queryBuilder->expr()->between("fl.longitude", ':secondCoordinateMinLongitude', ':secondCoordinateMaxLongitude'),
                        $queryBuilder->expr()->between("fl.latitude", ':secondCoordinateMinLatitude', ':secondCoordinateMaxLatitude'),
                    )
                )
            )->setParameters($queryParams);

        $entities = $queryBuilder->getQuery()->execute();

        return $entities;
    }

    /**
     * @param Location $firstLocation
     * @param Location $secondLocation
     * @param int $similarityPercentage
     *
     * @return array
     */
    private function buildPredictedDistanceQueryParams(Location $firstLocation, Location $secondLocation, int $similarityPercentage): array
    {
        // first coordinate
        $firstCoordinateLongitudeOffset = ($firstLocation->getLongitude() * $similarityPercentage / 100);
        $firstCoordinateLatitudeOffset  = ($firstLocation->getLatitude() * $similarityPercentage / 100);

        $isFirstCoordinateLongitudePositive = ($firstLocation->getLongitude() >= 0);
        $isFirstCoordinateLatitudePositive  = ($firstLocation->getLatitude() >= 0);

        if ($isFirstCoordinateLongitudePositive) {
            $firstCoordinateMinLongitude = $firstLocation->getLongitude() - $firstCoordinateLongitudeOffset;
            $firstCoordinateMaxLongitude = $firstLocation->getLongitude() + $firstCoordinateLongitudeOffset;
        } else {
            $firstCoordinateMinLongitude = $firstLocation->getLongitude() + $firstCoordinateLongitudeOffset;
            $firstCoordinateMaxLongitude = $firstLocation->getLongitude() - $firstCoordinateLongitudeOffset;
        }

        if ($isFirstCoordinateLatitudePositive) {
            $firstCoordinateMinLatitude  = $firstLocation->getLatitude() - $firstCoordinateLatitudeOffset;
            $firstCoordinateMaxLatitude  = $firstLocation->getLatitude() + $firstCoordinateLatitudeOffset;
        }else{
            $firstCoordinateMinLatitude  = $firstLocation->getLatitude() + $firstCoordinateLatitudeOffset;
            $firstCoordinateMaxLatitude  = $firstLocation->getLatitude() - $firstCoordinateLatitudeOffset;
        }
        // second coordinate
        $secondCoordinateLongitudeOffset = ($secondLocation->getLongitude() * $similarityPercentage / 100);
        $secondCoordinateLatitudeOffset  = ($secondLocation->getLatitude() * $similarityPercentage / 100);

        $isSecondCoordinateLongitudePositive = ($secondLocation->getLongitude() >= 0);
        $isSecondCoordinateLatitudePositive  = ($secondLocation->getLatitude() >= 0);

        if ($isSecondCoordinateLongitudePositive) {
            $secondCoordinateMinLongitude = $secondLocation->getLongitude() - $secondCoordinateLongitudeOffset;
            $secondCoordinateMaxLongitude = $secondLocation->getLongitude() + $secondCoordinateLongitudeOffset;
        } else {
            $secondCoordinateMinLongitude = $secondLocation->getLongitude() + $secondCoordinateLongitudeOffset;
            $secondCoordinateMaxLongitude = $secondLocation->getLongitude() - $secondCoordinateLongitudeOffset;
        }

        if ($isSecondCoordinateLatitudePositive) {
            $secondCoordinateMinLatitude  = $secondLocation->getLatitude() - $secondCoordinateLatitudeOffset;
            $secondCoordinateMaxLatitude  = $secondLocation->getLatitude() + $secondCoordinateLatitudeOffset;
        } else {
            $secondCoordinateMinLatitude  = $secondLocation->getLatitude() + $secondCoordinateLatitudeOffset;
            $secondCoordinateMaxLatitude  = $secondLocation->getLatitude() - $secondCoordinateLatitudeOffset;
        }

        $params = [
            'firstCoordinateMinLongitude'  => $firstCoordinateMinLongitude,
            'firstCoordinateMaxLongitude'  => $firstCoordinateMaxLongitude,
            'firstCoordinateMinLatitude'   => $firstCoordinateMinLatitude,
            'firstCoordinateMaxLatitude'   => $firstCoordinateMaxLatitude,
            'secondCoordinateMinLongitude' => $secondCoordinateMinLongitude,
            'secondCoordinateMaxLongitude' => $secondCoordinateMaxLongitude,
            'secondCoordinateMinLatitude'  => $secondCoordinateMinLatitude,
            'secondCoordinateMaxLatitude'  => $secondCoordinateMaxLatitude,
        ];

        return $params;
    }

    /**
     * Info: DO NOT remove it, will be used by {@see OfferExtractionService} if the distance related logic
     *       will ever be activated
     *
     * Will return {@see LocationDistance} entity for provided locations or null if nothing is found
     *
     * @param Location $firstLocation
     * @param Location $secondLocation
     *
     * @return LocationDistance|null
     *
     * @throws NonUniqueResultException
     */
    public function findByLocations(Location $firstLocation, Location $secondLocation): ?LocationDistance
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("ld")
                     ->from(LocationDistance::class, "ld")
                     ->join(Location::class, "fl", Join::WITH, "fl.id = ld.firstLocation")
                     ->join(Location::class, "sl", Join::WITH, "sl.id = ld.secondLocation")
                     ->where($queryBuilder->expr()->orX(
                         $queryBuilder->expr()->andX(
                             "ld.id = :firsLocationId",
                             "sl.id = :secondLocationId"
                         ),
                         $queryBuilder->expr()->andX(
                             "ld.id = :secondLocationId",
                             "sl.id = :firsLocationId"
                         ),
                     ))
                     ->setParameters([
                         "secondLocationId" => $firstLocation->getId(),
                         "firsLocationId"   => $secondLocation->getId(),
                     ]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
