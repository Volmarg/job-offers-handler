<?php

namespace JobSearcher\Service\Cleanup\Duplicate;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Company\CompanyBranchRepository;
use TypeError;

class CompanyBranchDuplicateHandlerService implements DuplicateCleanupInterface
{
    /**
     * Duplicates found within the set that is going to be used for cleanup.
     * So example: {@see CompanyRepository::findAllCreatedInDaysOffset()}
     * - returns branches from 24h, toward which clean is going to be handled
     * - in these 24h there might already exist duplicated branches,
     *   such branches then are stored in here
     *
     * @var CompanyBranch[] $innerDuplicates
     */
    private array $innerDuplicates = [];

    /**
     * @var CompanyBranch[] $removedDuplicates
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
     * @param CompanyBranchRepository $companyBranchRepository
     * @param EntityManagerInterface  $entityManager
     */
    public function __construct(
        private readonly CompanyBranchRepository $companyBranchRepository,
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
            $recentBranches = $this->companyBranchRepository->findAllCreatedInDaysOffset($maxDaysOffset);
            $this->cleanEntities($recentBranches);
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
     * @param CompanyBranch[] $branches
     *
     * @return CompanyBranch[]
     */
    private function filterInnerDuplicates(array $branches): array
    {
        $filteredBranches = [];
        $duplicatedBranchIds = [];

        foreach ($branches as $branch) {
            if (in_array($branch->getId(), $duplicatedBranchIds)) {
                $filteredBranches[] = $branch;
                continue;
            }

            foreach ($branches as $duplicateComparedBranch) {

                if (
                        $branch->getId()   !== $duplicateComparedBranch->getId()
                    &&  $branch->getAsMd5() === $duplicateComparedBranch->getAsMd5()
                ) {
                    $duplicatedBranchIds[]  = $duplicateComparedBranch->getId();
                    $this->innerDuplicates[] = $branch;
                    continue 2;
                }

            }

            $filteredBranches[] = $branch;
        }

        return $filteredBranches;
    }

    /**
     * @param CompanyBranch[] $branches
     */
    private function mergeInnerDuplicates(array $branches): void
    {
        foreach ($this->innerDuplicates as $innerDuplicate) {
            foreach ($branches as $branch) {

                if ($innerDuplicate->getAsMd5() === $branch->getAsMd5()) {
                    $this->mergeData($branch, $innerDuplicate);
                    $this->countOfCleared++;
                    continue 2;
                }

            }
        }
    }

    /**
     * Merge data from one company to another
     *
     * @param CompanyBranch $mergedInto
     * @param CompanyBranch $mergedFrom
     */
    private function mergeData(CompanyBranch $mergedInto, CompanyBranch $mergedFrom): void
    {
        if (!$mergedInto->getLocation()) {
            $mergedInto->setLocation($mergedFrom->getLocation());
        }

        $fromPhoneNumbers = $mergedFrom->getPhoneNumbers() ?? [];
        foreach ($fromPhoneNumbers as $phoneNumber) {
            $mergedInto->addUniquePhoneNumber($phoneNumber);
        }

        /** @var JobSearchResult $jobOffer */
        foreach ($mergedFrom->getJobSearchResults() as $jobOffer) {
            $jobOffer->setCompanyBranch($mergedInto);
            $this->entityManager->persist($jobOffer);
        }


        $this->entityManager->persist($mergedInto);
        $this->entityManager->flush();

        self::$removedDuplicates[] = $mergedFrom;
    }

}