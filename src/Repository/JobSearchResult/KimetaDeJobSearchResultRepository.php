<?php

namespace JobSearcher\Repository\JobSearchResult;

use JobSearcher\Entity\JobSearchResult\KimetaDeJobSearchResult;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method KimetaDeJobSearchResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method KimetaDeJobSearchResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method KimetaDeJobSearchResult[]    findAll()
 * @method KimetaDeJobSearchResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KimetaDeJobSearchResultRepository extends JobSearchResultRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KimetaDeJobSearchResult::class);
    }

}
