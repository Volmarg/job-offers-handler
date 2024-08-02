<?php

namespace JobSearcher\Repository\Storage;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use JobSearcher\Constants\RabbitMq\Common\CommunicationConstants;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\Storage\AmqpStorage;
use JobSearcher\RabbitMq\Producer\JobSearch\JobSearchDoneProducer;

/**
 * @method AmqpStorage|null find($id, $lockMode = null, $lockVersion = null)
 * @method AmqpStorage|null findOneBy(array $criteria, array $orderBy = null)
 * @method AmqpStorage[]    findAll()
 * @method AmqpStorage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AmqpStorageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AmqpStorage::class);
    }

    /**
     * That's a bit messy, but {@see AmqpStorage} is the only place where any relation
     * between client id and {@see JobOfferExtraction} can be found.
     *
     * - first doing LIKE % to reduce the amount of entries to work with (the searchId is stored in message json),
     * - further going over the data arrays (created from earlier mentioned json), and checking exact match for id,
     *
     * @param array $searchIds
     *
     * @return AmqpStorage[]
     */
    public function findBySearchIds(array $searchIds): array
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select("amqp")
           ->from(AmqpStorage::class, "amqp")
           ->where("amqp.message LIKE :searchId")
           ->andWhere("amqp.targetClass = :targetClass")
           ->setParameter('targetClass', JobSearchDoneProducer::class);

        /** @var AmqpStorage[] $lazyMatchingEntities */
        $lazyMatchingEntities = [];
        foreach ($searchIds as $searchId) {
            $resultEntities = $qb
                ->setParameter("searchId", "%{$searchId}%")
                ->getQuery()
                ->getResult();

            $lazyMatchingEntities = array_merge($lazyMatchingEntities, $resultEntities);
        }

        $filteredEntities = [];
        foreach ($searchIds as $searchId) {
            foreach ($lazyMatchingEntities as $entity) {
                $messageData = json_decode($entity->getMessage(), true);
                if ($messageData[CommunicationConstants::KEY_SEARCH_ID] === $searchId) {
                    $filteredEntities[] = $entity;
                    break;
                }
            }
        }

        return $filteredEntities;
    }
}
