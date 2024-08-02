<?php

namespace JobSearcher\Service\Cleanup\Duplicate;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Repository\LocationRepository;
use TypeError;

class LocationDuplicateHandlerService implements DuplicateCleanupInterface
{
    /**
     * Duplicates found within the set that is going to be used for cleanup.
     * So example: {@see LocationRepository::findAllCreatedInDaysOffset()}
     * - returns locations from 24h, toward which clean is going to be handled
     * - in these 24h there might already exist duplicated locations,
     *   such locations then are stored in here
     *
     * @var Location[] $innerDuplicates
     */
    private array $innerDuplicates = [];

    /**
     * @var Location[] $removedDuplicates
     */
    private static array $removedDuplicates = [];

    /**
     * @var int $countOfCleared
     */
    private int $countOfCleared = 0;

    /**
     * @return array
     */
    public static function getRemovedDuplicates(): array
    {
        return self::$removedDuplicates;
    }

    /**
     * @param LocationRepository     $locationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly EntityManagerInterface  $entityManager
    ){

    }

    /**
     * {@inheritDoc}
     */
    public function clean(int $maxDaysOffset, array $extractionIds = []): int
    {
        $this->entityManager->beginTransaction();
        try {
            $recentLocations = $this->locationRepository->findAllCreatedInDaysOffset($maxDaysOffset);
            $this->cleanEntities($recentLocations);
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->countOfCleared;
    }

    /**
     * @param array $entities
     */
    public function cleanEntities(array $entities): void
    {
        $filteredBranches = $this->filterInnerDuplicates($entities);
        $this->mergeInnerDuplicates($filteredBranches);
    }

    /**
     * @param Location[] $locations
     *
     * @return Location[]
     */
    private function filterInnerDuplicates(array $locations): array
    {
        $filteredLocations = [];
        $duplicatedLocationsIds = [];

        foreach ($locations as $location) {
            if (in_array($location->getId(), $duplicatedLocationsIds)) {
                $filteredLocations[] = $location;
                continue;
            }

            foreach ($locations as $duplicateLocation) {

                if (
                        $location->getId()   !== $duplicateLocation->getId()
                    &&  $location->getAsMd5() === $duplicateLocation->getAsMd5()
                ) {
                    $duplicatedLocationsIds[]  = $duplicateLocation->getId();
                    $this->innerDuplicates[] = $location;
                    continue 2;
                }

            }

            $filteredLocations[] = $location;
        }

        return $filteredLocations;
    }

    /**
     * @param Location[] $locations
     */
    private function mergeInnerDuplicates(array $locations): void
    {
        foreach ($this->innerDuplicates as $innerDuplicate) {
            foreach ($locations as $location) {

                if ($innerDuplicate->getAsMd5() === $location->getAsMd5()) {
                    $this->mergeData($location, $innerDuplicate);
                    $this->countOfCleared++;
                    continue 2;
                }

            }
        }
    }

    /**
     * Merge data from one location to another
     *
     * @param Location $mergedInto
     * @param Location $mergedFrom
     */
    private function mergeData(Location $mergedInto, Location $mergedFrom): void
    {
        /** @var CompanyBranch $branch */
        foreach ($mergedFrom->getCompanyBranches()->getValues() as $branch) {
            $branch->setLocation($mergedInto);
            $this->entityManager->persist($branch);
        }

        $this->entityManager->persist($mergedInto);
        $this->entityManager->flush();

        self::$removedDuplicates[] = $mergedFrom;
    }

}