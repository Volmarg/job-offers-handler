<?php

namespace JobSearcher\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use JobSearcher\Entity\Location\Location;

/**
 * @method Location|null find($id, $lockMode = null, $lockVersion = null)
 * @method Location|null findOneBy(array $criteria, array $orderBy = null)
 * @method Location[]    findAll()
 * @method Location[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * Will search for one entry that matches the provided params
     * For more information see description of called {@see LocationRepository::findAllExisting()}
     *
     * @param string      $name
     * @param string|null $country
     * @param float|null  $longitude
     * @param float|null  $latitude
     *
     * @return Location|null
     */
    public function findFirstExisting(string $name, ?string $country = null, ?float $longitude = null, ?float $latitude = null): ?Location
    {
        $allExistingLocations = $this->findAllExisting($name, $country, $longitude, $latitude);
        $mostFilledEntry      = null;
        $isTwoParamMatch      = false;
        $isNameMatch          = false;

        foreach ($allExistingLocations as $location) {
            if(
                    $location->getCountry()
                &&  $location->getLongitude()
                &&  $location->getLatitude()
            ){
                $mostFilledEntry = $location;
                break;
            }

            if ($isTwoParamMatch) {
                continue;
            }

            if(
                    $location->getCountry()
                &&  $location->getLongitude()
                ||
                    (
                            $location->getCountry()
                        &&  $location->getLatitude()
                    )
                ||
                    (
                            $location->getLongitude()
                        &&  $location->getLatitude()
                    )
            ){
                $mostFilledEntry = $location;
                $isTwoParamMatch = true;
            }

            if ($isNameMatch) {
                continue;
            }

            if ($location->getName() === $name) {
                $mostFilledEntry = $location;
                $isNameMatch     = true;
            }
        }

        return $mostFilledEntry;
    }

    /**
     * Will find all existing locations for given params
     * This search is a bit tricky since it has to search via all possible combinations as for example:
     * - name + country,
     * - only name,
     * - name + longitude,
     * - etc.
     *
     * That's needed since some locations might be missing some data,
     *
     * @param string      $name
     * @param string|null $country
     * @param float|null  $longitude
     * @param float|null  $latitude
     *
     * @return Location[]
     */
    public function findAllExisting(string $name, ?string $country = null, ?float $longitude = null, ?float $latitude = null): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        // where order matters, first the more accurate matches should be used
        $queryBuilder->select("l")
                     ->from(Location::class, "l")
                     ->where(
                         $queryBuilder->expr()->orX(
                             $queryBuilder->expr()->andX(
                                 "l.name  = :name",
                                 "l.country = :country",
                                 "l.longitude = :longitude",
                                 "l.latitude = :latitude"
                             ),
                             $queryBuilder->expr()->andX(
                                 "l.name  = :name",
                                 "l.country = :country",
                                 "l.longitude = :longitude"
                             ),
                             $queryBuilder->expr()->andX(
                                 "l.name  = :name",
                                 "l.country = :country",
                                 "l.latitude = :latitude"
                             ),
                             $queryBuilder->expr()->andX(
                                 "l.name  = :name",
                                 "l.longitude = :longitude",
                                 "l.latitude = :latitude"
                             ),
                             $queryBuilder->expr()->andX(
                                 "l.name = :name",
                                 "l.country = :country"
                             ),
                             $queryBuilder->expr()->andX(
                                 "l.name  = :name",
                                 "l.longitude = :longitude"
                             ),
                             $queryBuilder->expr()->andX(
                                 "l.name  = :name",
                                 "l.latitude = :latitude"
                             ),
                             $queryBuilder->expr()->eq("l.name", ":name"),
                         )
                     )
                     ->setParameter("name", $name)
                     ->setParameter("latitude", $latitude)
                     ->setParameter("longitude", $longitude)
                     ->setParameter("country", $country);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return all locations without country
     *
     * @return Location[]
     */
    public function findAllWithoutCountry(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("l")
            ->from(Location::class, "l")
            ->where("l.country IS NULL");

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param int $maxDaysOffset
     *
     * @return Location[]
     */
    public function findAllCreatedInDaysOffset(int $maxDaysOffset): array
    {
        $minDate = (new DateTime())->modify("-{$maxDaysOffset} DAY")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("l")
             ->from(Location::class, "l")
             ->where("l.created >= :minDate")
             ->setParameter("minDate", $minDate)
             ->orderBy("l.created", "ASC");

        return $queryBuilder->getQuery()->execute();
    }

}
