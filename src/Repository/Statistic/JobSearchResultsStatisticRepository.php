<?php

namespace JobSearcher\Repository\Statistic;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\DTO\Statistic\JobSearch\CountOfUniquePerDayDto;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Service\Serialization\ObjectSerializerService;

class JobSearchResultsStatisticRepository
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly ObjectSerializerService $objectSerializerService
    ){}

    /**
     * Returns array statistic which show how many unique offers were found per day in month of year.
     * Results rows are getting mapped to {@see CountOfUniquePerDayDto}
     *
     * The comparison of created / modified date of extraction and job search result:
     *  - this ensures that only unique (first time found) offers are counted by:
     *    - taking the first time found extraction,
     *    - making sure that first time found extraction date = offer date
     *
     *    This might cause some wrong results tho if extraction starts on previous day and finishes on next, thus
     *    checking not only created / but also modified date, since job extraction changes its state before being
     *    done, so modified date is changed too.
     *
     * @param array $extractionIds
     *
     * @return CountOfUniquePerDayDto[]
     *
     * @throws Exception
     */
    public function getCountOfUniquePerDay(array $extractionIds): array
    {
        if (empty($extractionIds)) {
            return [];
        }

        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder->select("
            COUNT(jsr.id)                  AS offersCount,
            CAST(DATE_FORMAT(jsr.created, '%Y') AS UNSIGNED) AS year,
            CAST(DATE_FORMAT(jsr.created, '%c') AS UNSIGNED) AS month,
            CAST(DATE_FORMAT(jsr.created, '%e') AS UNSIGNED) AS day,
            ex.id                          AS extractionId
        ")
        ->from(JobSearchResult::class, "jsr")
        ->join("jsr.extractions", "ex")
        ->where($queryBuilder->expr()->in("ex.id", $extractionIds))
        ->andWhere($queryBuilder->expr()->isNotNull("jsr.companyBranch"))
        ->andWhere("jsr.firstTimeFoundExtraction = ex.id")
        ->groupBy("year")
        ->addGroupBy("month")
        ->addGroupBy("day");

        $results = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute();
        $dtos    = array_map(
        fn(array $row) => $this->objectSerializerService->fromJson(json_encode($row),CountOfUniquePerDayDto::class),
            $results,
        );

        return $dtos;
    }
}
