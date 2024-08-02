<?php

namespace JobSearcher\Repository\Email;

use JobSearcher\Entity\Email\Email2Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Email2Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Email2Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Email2Company[]    findAll()
 * @method Email2Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Email2CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Email2Company::class);
    }

}
