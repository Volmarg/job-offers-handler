<?php

namespace JobSearcher\Repository\JobSearchResult;

use JobSearcher\Entity\JobSearchResult\StepstoneDeJobSearchResult;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StepstoneDeJobSearchResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method StepstoneDeJobSearchResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method StepstoneDeJobSearchResult[]    findAll()
 * @method StepstoneDeJobSearchResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StepstoneDeJobSearchResultRepository extends JobSearchResultRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepstoneDeJobSearchResult::class);
    }

}
