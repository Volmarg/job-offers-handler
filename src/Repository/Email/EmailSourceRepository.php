<?php

namespace JobSearcher\Repository\Email;

use JobSearcher\Entity\Email\EmailSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailSource|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSource|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSource[]    findAll()
 * @method EmailSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailSource::class);
    }

}
