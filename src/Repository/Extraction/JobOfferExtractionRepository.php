<?php

namespace JobSearcher\Repository\Extraction;

use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Exception\EntityManagerClosed;
use JobSearcher\Entity\Extraction\ExtractionKeyword2Offer;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method JobOfferExtraction|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobOfferExtraction|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobOfferExtraction[]    findAll()
 * @method JobOfferExtraction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobOfferExtractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOfferExtraction::class);
    }

    /**
     * Will create or update existing entry
     *
     * @param JobOfferExtraction $jobOfferExtraction
     */
    public function save(JobOfferExtraction $jobOfferExtraction): void
    {
        $this->_em->persist($jobOfferExtraction);
        $this->_em->flush();
    }

    /**
     * @param int $minutesOffset
     *
     * @return JobOfferExtraction[]
     */
    public function findRunningLongerThan(int $minutesOffset = 60): array
    {
        $queryBuilder   = $this->_em->createQueryBuilder();
        $createdMinDate = (new DateTime())->modify("-{$minutesOffset} MINUTES");

        $queryBuilder->select("joe")
            ->from(JobOfferExtraction::class, "joe")
            ->where("joe.created < :minDate")
            ->andWhere("joe.status = :status")
            ->setParameter("minDate", $createdMinDate, Types::DATETIME_MUTABLE)
            ->setParameter("status", JobOfferExtraction::STATUS_IN_PROGRESS, Types::STRING);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will return the {@see JobOfferExtraction} older than provided date
     *
     * @param DateTime $maxDate
     *
     * @return JobOfferExtraction[]
     */
    public function findOlderThan(DateTime $maxDate): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("joe")
            ->from(JobOfferExtraction::class, "joe")
            ->where("joe.created < :maxDate")
            ->setParameter("maxDate", $maxDate)
            ->orderBy("joe.id", "ASC");

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $status
     *
     * @return JobOfferExtraction[]
     */
    public function findAllWithAmqpByStatus(string $status): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("joe")
            ->from(JobOfferExtraction::class, "joe")
            ->join('joe.extraction2AmqpRequest', 'e2a')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->isNotNull('e2a.id'),
                    $queryBuilder->expr()->eq("joe.status", ':status')
                )
            )->setParameter('status', $status);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Will try to find average count of offers found for the given keyword so far,
     *
     * @param string   $keyword
     * @param int|null $offersLimit
     *
     * @return int|null
     */
    public function getAverageOffersCountForKeyword(string $keyword, ?int $offersLimit = null): ?int
    {
        $qb = $this->_em->createQueryBuilder()
            ->select("
                COUNT(ek2o.id) AS counted
            ")->from(ExtractionKeyword2Offer::class,"ek2o")
            ->where("ek2o.keyword = :keyword")
            ->setParameter("keyword", $keyword)
            ->groupBy("ek2o.extraction");

        if (!is_null($offersLimit)) {
            $qb->join("ek2o.extraction", "e")
                ->andWhere("e.offersLimit = :limit")
                ->setParameter("limit", $offersLimit);
        }

        $result = $qb->getQuery()->execute();
        $values = array_map(
            fn(array $subArray) => $subArray['counted'],
            $result
        );

        if (empty($values)) {
            return null;
        }

        $avg = array_sum($values) / count($values);

        return (int)$avg;
    }

    /**
     * The plain SQL is there on purpose.
     *
     * That's because it can happen that during extraction there will be {@see EntityManagerClosed} etc.
     * which breaks further handling of code.
     *
     * If that happens then this function can be used to set status without using entity manager.
     * There were cases where due to exception the status would never be set to {@see JobOfferExtraction::STATUS_FAILED}
     *
     * @param int    $extractionId
     * @param string $status
     *
     * @throws Exception
     */
    public function updateExtractionStatus(int $extractionId, string $status): void
    {
        $sql = "
            UPDATE job_offer_extraction
            SET status = :status
            WHERE id = :id
        ";

        $params = [
            'status' => $status,
            'id'     => $extractionId,
        ];

        $this->_em->getConnection()->executeQuery($sql, $params);
    }

    /**
     * Will return count of offers found for given extraction ids.
     * This is not the {@see JobOfferExtraction::$extractionCount} because some
     * offers are invalid and are not presented to user thus excluding:
     * - offers without company branches
     *
     * and counting directly the offers amount
     *
     * @param array $extractionIds
     *
     * @return array
     */
    public function getFoundOffersCount(array $extractionIds): array
    {
        if (empty($extractionIds)) {
            return [];
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->select("
            COUNT(jsr.id) AS offersCount,
            e.id          AS extractionId
        ")
            ->from(JobOfferExtraction::class, "e")
            ->join("e.jobSearchResults", "jsr")
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in("e.id", ":ids"),
                    $qb->expr()->isNotNull("jsr.companyBranch")
                )
            )
            ->groupBy("e.id")
            ->setParameter('ids', $extractionIds);

        $result         = $qb->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute();
        $normalizedData = [];
        foreach ($result as $rowData) {
            $extractionId = $rowData['extractionId'];
            $count        = $rowData['offersCount'];

            $normalizedData[$extractionId] = $count;
        }

        return $normalizedData;
    }
}
