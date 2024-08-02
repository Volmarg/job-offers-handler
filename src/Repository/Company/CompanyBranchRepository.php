<?php

namespace JobSearcher\Repository\Company;

use DateTime;
use JobSearcher\Entity\Company\CompanyBranch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CompanyBranch|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyBranch|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyBranch[]    findAll()
 * @method CompanyBranch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<CompanyBranch>
 */
class CompanyBranchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyBranch::class);
    }

    /**
     * @param int $maxDaysOffset
     *
     * @return CompanyBranch[]
     */
    public function findAllCreatedInDaysOffset(int $maxDaysOffset): array
    {
        $minDate = (new DateTime())->modify("-{$maxDaysOffset} DAY")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("cb")
             ->from(CompanyBranch::class, "cb")
             ->where("cb.created >= :minDate")
             ->setParameter("minDate", $minDate)
             ->orderBy("cb.created", "ASC");

        return $queryBuilder->getQuery()->execute();
    }
}
