<?php

namespace JobSearcher\Repository\Service;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use JobSearcher\Entity\Service\ServiceState;

/**
 * @method ServiceState|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServiceState|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServiceState[]    findAll()
 * @method ServiceState[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceState::class);
    }

    /**
     * Will return {@see ServiceState} entry for given service name, or null if none is found
     *
     * @param string $name
     *
     * @return ServiceState|null
     */
    public function findByName(string $name): ?ServiceState
    {
        return $this->findOneBy([
            "name" => $name,
        ]);
    }

}
