<?php

namespace JobSearcher\Repository\Extraction;

use JobSearcher\Entity\Extraction\Extraction2AmqpRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\Storage\AmqpStorage;

/**
 * @method Extraction2AmqpRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method Extraction2AmqpRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method Extraction2AmqpRequest[]    findAll()
 * @method Extraction2AmqpRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Extraction2AmqpRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Extraction2AmqpRequest::class);
    }

    /**
     * Will return {@see JobOfferExtraction} if it exists for id of {@see AmqpStorage}, else {@see null} is returned
     *
     * @param int $amqpStorageId
     *
     * @return JobOfferExtraction|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getExtractionForAmqpStorageId(int $amqpStorageId): ?JobOfferExtraction
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("e2q")
            ->from(Extraction2AmqpRequest::class, "e2q")
            ->where("e2q.amqpRequest = :id")
            ->setParameter("id", $amqpStorageId);

        $extraction2amqp = $queryBuilder->getQuery()->getOneOrNullResult();
        if (empty($extraction2amqp)) {
            return null;
        }

        return $extraction2amqp->getExtraction();

    }
}
