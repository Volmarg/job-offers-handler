<?php

namespace JobSearcher\Repository\JobSearchResult;

use JobSearcher\Entity\JobSearchResult\IndeedDeJobSearchResult;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method IndeedDeJobSearchResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method IndeedDeJobSearchResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method IndeedDeJobSearchResult[]    findAll()
 * @method IndeedDeJobSearchResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndeedDeJobSearchResultRepository extends JobSearchResultRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndeedDeJobSearchResult::class);
    }

}
