<?php

namespace JobSearcher\Repository\Extraction;

use JobSearcher\Entity\Extraction\ExtractionKeyword2Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExtractionKeyword2Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExtractionKeyword2Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExtractionKeyword2Offer[]    findAll()
 * @method ExtractionKeyword2Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExtractionKeyword2OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtractionKeyword2Offer::class);
    }

}
