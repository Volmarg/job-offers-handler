<?php

namespace JobSearcher\Repository\Company;

use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Generator;
use JobSearcher\Entity\Company\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use JobSearcher\Entity\Email\Email2Company;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Will find one company by the name or country
     *
     * @param string $companyName
     *
     * @return Company|null
     * @throws NonUniqueResultException
     */
    public function findOneCompany(string $companyName): ?Company
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Company::class, "c")
            ->where("c.name = :companyName")
            ->setParameter("companyName", $companyName)
            /**
             * Because there can be multiple present, duplicates are cleared at night
             * That's the issue with concurrency
             */
            ->setMaxResults(1)
            ->orderBy("c.id", "ASC");

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Will return the entities that can be/should be used for providing the company data
     *
     * @return Company[]
     */
    public function findForCompanyDataProvider(DateTime $minLastSearchRun): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("c")
            ->from(Company::class, "c")
            ->join(Email2Company::class, "e2c", Join::WITH, "c.id = e2c.company")

            // because fetching only for companies that have some offers at all
            ->join(JobSearchResult::class, "jsr", Join::WITH, "jsr.company = c.id");

        $queryBuilder->where(
            $queryBuilder->expr()->orX(

                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq("e2c.forJobApplication", 0),
                    $queryBuilder->expr()->isNull("e2c.id")
                ),

                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull("c.website"),
                    $queryBuilder->expr()->isNull("c.linkedinUrl")
                )

            )
        );

        $queryBuilder->andWhere("c.lastDataSearchRun <= :minLastSearchRun")
            ->setParameter("minLastSearchRun", $minLastSearchRun);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return the result to iterate over
     *
     * @param int|null $createdYear
     * @param int|null $createdMonth
     * @param bool     $withWebsitesOnly
     *
     * @return Generator
     */
    public function findAllWithoutApplicationEmail(?int $createdYear = null, ?int $createdMonth = null, bool $withWebsitesOnly = true): Generator
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("c")
                     ->from(Company::class, "c")
                     ->join(Email2Company::class, "e2c", Join::WITH, "
                         e2c.id = (
                            SELECT MAX(e2c_one.id) FROM " . Email2Company::class . " as e2c_one 
                            WHERE e2c_one.company = c.id
                            AND e2c_one.forJobApplication = 1
                        )"
                     )
                    ->where("1 = 1")
                    ->orderBy("c.id", "DESC");

        if (!empty($createdYear)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq("DATE_FORMAT(c.created, '%Y')", ":createdYear")
            )->setParameter("createdYear", $createdYear);
        }

        if (!empty($createdYear)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq("DATE_FORMAT(c.created, '%m')", ":createdMonth")
            )->setParameter("createdMonth", $createdMonth);
        }

        if ($withWebsitesOnly) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->isNotNull("c.website")
            );
        }

        return $queryBuilder->getQuery()->toIterable();
    }

    /**
     * Will return companies that were not related to any offer for given amount of days
     *
     * @param int $daysOffset
     * @param int $limit
     * @return Company[]
     */
    public function findAllWithoutOffersRelatedForDays(int $daysOffset, int $limit): array
    {
        $minLastTimeRelatedToOffers = (new DateTime())->modify("-{$daysOffset} DAYS");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Company::class, "c")
            ->where($queryBuilder->expr()->orX(
                "c.lastTimeRelatedToOffer IS NULL",
                "c.lastTimeRelatedToOffer < :minLastTimeRelatedToOffers",
            ))->setParameter('minLastTimeRelatedToOffers', $minLastTimeRelatedToOffers)
            ->orderBy("c.id", "ASC")
            ->setMaxResults($limit);

        $foundCompanies = $queryBuilder->getQuery()->execute();

        return $foundCompanies;
    }

    /**
     * @param int $maxDaysOffset
     *
     * @return Company[]
     */
    public function findAllCreatedInDaysOffset(int $maxDaysOffset): array
    {
        $minDate = (new DateTime())->modify("-{$maxDaysOffset} DAY")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("c")
            ->from(Company::class, "c")
            ->where("c.created >= :minDate")
            ->setParameter("minDate", $minDate)
            ->orderBy("c.created", "ASC");

        return $queryBuilder->getQuery()->execute();
    }

}
