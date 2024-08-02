<?php

namespace JobSearcher\Repository\Statistic;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Email\Email2Company;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\QueryBuilder\Modifier\Single\Statistic\ContactEmailStatistic\MustNotHaveApplicationEmailModifier;

/**
 * Contains statistic regarding the company / offer contact emails
 */
class ContactEmailStatisticRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    /**
     * Will return count of offers without any E-Mail that can be used for application,
     * Results are grouped by day of months
     *
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function countOffersWithoutApplicationEmail(int $month, int $year): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select("
            DATE_FORMAT(jsr.created, '%Y-%m-%d') AS date,
            COUNT(jsr.id)                        AS count
        ")->from(JobSearchResult::class,"jsr")
            ->where("DATE_FORMAT(jsr.created, '%Y') = :year")
            ->andWhere("DATE_FORMAT(jsr.created, '%m') = :month")
            ->groupBy('date')
            ->orderBy("jsr.created", "DESC")
            ->setParameter("year", $year, Types::INTEGER)
            ->setParameter("month", $month, Types::INTEGER);

        MustNotHaveApplicationEmailModifier::apply($queryBuilder);

        $results = $queryBuilder->getQuery()->execute();

        $normalizedResult = [];
        foreach ($results as $result) {
            $normalizedResult[$result['date']] = $result['count'];
        }

        return $normalizedResult;
    }

    /**
     * Will return count of offers with E-Mail that can be used for application,
     * Results are grouped by day of months
     *
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function countOffersWithApplicationEmail(int $month, int $year): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select("
            DATE_FORMAT(jsr.created, '%Y-%m-%d') AS date,
            COUNT(jsr.id)                        AS count
        ")->from(JobSearchResult::class,"jsr")
             ->join(Company::class, "c", Join::WITH, "c.id = jsr.company")
             ->leftJoin(Email2Company::class, "e2c", Join::WITH, "
                e2c.id = (
                    SELECT MAX(e2c_one.id) FROM " . Email2Company::class . " as e2c_one 
                    WHERE e2c_one.company = c.id
                    AND e2c_one.forJobApplication = 1
                )
             ")
             ->where("DATE_FORMAT(jsr.created, '%Y') = :year")
             ->andWhere("DATE_FORMAT(jsr.created, '%m') = :month")
             ->andWhere(
                 $queryBuilder->expr()->orX(
                     $queryBuilder->expr()->eq("e2c.forJobApplication", 1),
                     $queryBuilder->expr()->isNotNull("jsr.email")
                 )
             )
             ->groupBy('date')
             ->orderBy("jsr.created", "DESC")
             ->setParameter("year", $year, Types::INTEGER)
             ->setParameter("month", $month, Types::INTEGER);

        $results = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute();

        $normalizedResult = [];
        foreach ($results as $result) {
            $normalizedResult[$result['date']] = $result['count'];
        }

        return $normalizedResult;
    }

    /**
     * Will return offers without any E-Mail, with some base information for debugging / analyze process
     *
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function getOffersWithoutEmails(int $month, int $year): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select("
            DATE_FORMAT(jsr.created, '%Y-%m-%d') AS date,
            jsr.id                               AS offerId,
            c.name                               AS companyName,
            c.linkedinUrl                        AS linkedinUrl,
            c.website                            AS website
        ")->from(JobSearchResult::class,"jsr")
             ->where("DATE_FORMAT(jsr.created, '%Y') = :year")
             ->andWhere("DATE_FORMAT(jsr.created, '%m') = :month")
             ->orderBy("jsr.created", "DESC")
             ->setParameter("year", $year, Types::INTEGER)
             ->setParameter("month", $month, Types::INTEGER);

        MustNotHaveApplicationEmailModifier::apply($queryBuilder);

        $results = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute();

        return $results;
    }

}