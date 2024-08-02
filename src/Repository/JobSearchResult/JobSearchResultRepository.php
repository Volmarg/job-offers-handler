<?php

namespace JobSearcher\Repository\JobSearchResult;

use BadFunctionCallException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use JobSearcher\Doctrine\Type\ArrayType;
use JobSearcher\DTO\JobService\SearchFilter\JobOfferFilterDto;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\BaseFilterModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MatchCountryNameModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MatchMaxSalaryModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MatchMinCompanyYearsOldModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MatchMinSalaryModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MatchPostedDateTimeRangeModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustBeRemoteModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustExcludeCompanyName;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHaveCompanyAgeOldModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHaveCountryNameModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHaveEmailModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHaveEmployeeCountModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHaveLanguageDetectedModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHaveLocationModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHavePhoneModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustHaveSalaryModifier;
use JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter\MustIncludeCompanyName;

/**
 * Common logic for handling job search result entities
 *
 * @method JobSearchResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobSearchResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobSearchResult[]    findAll()
 * @method JobSearchResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobSearchResultRepository extends ServiceEntityRepository
{
    /**
     * @var string $usedEntityNamespace
     */
    private string $usedEntityNamespace;

    /**
     * @param ManagerRegistry $registry
     * @param string          $entityClassNamespace
     */
    public function __construct(ManagerRegistry $registry, string $entityClassNamespace = JobSearchResult::class)
    {
        $this->usedEntityNamespace = JobSearchResult::class;
        parent::__construct($registry, $entityClassNamespace);
    }

    /**
     * Will update existing entity or create new one
     *
     * @param JobSearchResult $jobSearchResult
     *
     */
    public function save(JobSearchResult $jobSearchResult): void
    {
        $this->denyForAbstractEntity($jobSearchResult);
        $this->_em->persist($jobSearchResult);
        $this->_em->flush();
    }

    /**
     * Will search for offers ids by urls (in all tables extending from {@see JobSearchResult})
     * - return the found offers ids, where key is ID and value is url
     *
     * @param array $offerUrls
     * @param DateTime|null $createdAfter
     * @return array
     */
    public function getExistingOfferIdsForUrls(array $offerUrls, ?DateTime $createdAfter = null): array
    {
        $idsWithUrls  = [];
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(
            "
                MAX(jbs.id) AS id, -- with this always the latest offer (if duplicate exist) will be used
                jbs.jobOfferUrl AS jobOfferUrl
            ")
            ->from(JobSearchResult::class, "jbs")
            ->where("jbs.jobOfferUrl IN (:offersUrls)")
            ->setParameter("offersUrls", $offerUrls, Connection::PARAM_STR_ARRAY)
            ->groupBy("jobOfferUrl");

        if (!empty($createdAfter)) {
            $queryBuilder->andWhere("jbs.created > :date")
                ->setParameter("date", $createdAfter->format("Y-m-d"));
        }

        $entityData = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_SCALAR)->execute();
        foreach ($entityData as $dataArrays) {
            $id          = $dataArrays['id'];
            $jobOfferUrl = $dataArrays['jobOfferUrl'];

            $idsWithUrls[$id] = $jobOfferUrl;
        }

        return $idsWithUrls;
    }

    /**
     * Will search for existing offers by "company name" and "job title" hashes (in all tables extending from {@see JobSearchResult})
     * - returns array of hashes for found ids, where key is id and value is hash
     *
     * @param string[] $hashes
     * @param DateTime|null $createdAfter
     * @return array
     */
    public function getIdsForCompanyNameAndJobTitleHashes(array $hashes, ?DateTime $createdAfter = null): array
    {
        $idsWithHashes = [];
        $queryBuilder  = $this->_em->createQueryBuilder();
        $queryBuilder->select(
            "
                MAX(jbs.id) AS id, -- with this always the latest offer (if duplicate exist) will be used
                MD5(CONCAT(c.name, jbs.jobTitle)) AS hash
            ")
            ->from(JobSearchResult::class, "jbs")
            ->join(Company::class, "c", Join::WITH, "c.id = jbs.company")
            ->where("MD5(CONCAT(c.name, jbs.jobTitle)) IN (:hashes)")
            ->setParameter("hashes", $hashes, Connection::PARAM_STR_ARRAY)
            ->groupBy("hash");

        if (!empty($createdAfter)) {
            $queryBuilder->andWhere("jbs.created > :date")
                ->setParameter("date", $createdAfter->format("Y-m-d"));
        }

        $entityData = $queryBuilder->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_SCALAR)->execute();
        foreach ($entityData as $dataArrays) {
            $id   = $dataArrays['id'];
            $hash = $dataArrays['hash'];

            $idsWithHashes[$id] = $hash;
        }

        return $idsWithHashes;
    }

    /**
     * Will return all job offers without keywords
     *
     * @return JobSearchResult[]
     */
    public function getAllWithoutKeywords(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jbs")
                     ->from(JobSearchResult::class, "jbs")
                     ->where("jbs.keywords = :empty")
                     ->orWhere("jbs.keywords = :emptySerializedArray")
                     ->setParameter("empty", ArrayType::EMPTY_ARRAY_VALUE)
                     ->setParameter("emptySerializedArray", ArrayType::SERIALIZED_EMPTY_ARRAY_VALUE);

        $allJobSearchResults = $queryBuilder->getQuery()->execute();
        return $allJobSearchResults;
    }

    /**
     * Will return all job offers without languages
     *
     * @return JobSearchResult[]
     */
    public function getAllWithoutLanguages(): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jbs")
                     ->from(JobSearchResult::class, "jbs")
                     ->where("jbs.mentionedHumanLanguages = :empty")
                     ->setParameter("empty", ArrayType::EMPTY_ARRAY_VALUE)
                     ->orderBy("jbs.id", "DESC");

        $allJobSearchResults = $queryBuilder->getQuery()->execute();
        return $allJobSearchResults;
    }

    /**
     * Returns job offers by the {@see JobOfferFilterDto}
     * Not all checks are done as it's going to be hard on database side,
     * If possible, then as many checks as possible should be added in here (DB side)
     * before fetching results to prevent memory overloading and reducing processing time
     *
     * @param JobOfferFilterDto    $jobOfferFilterDto
     * @param JobOfferExtraction[] $jobOfferExtractions
     *
     * @return JobSearchResult[]
     * @throws Exception
     */
    public function findByFilterDto(JobOfferFilterDto $jobOfferFilterDto, array $jobOfferExtractions): array
    {
        $maxResults = $this->calculateMaxResultsInSearchByFilter($jobOfferExtractions);

        $jobOfferFilterDto->validateSelf();

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jbs")
        ->from(JobSearchResult::class, "jbs")
        ->join('jbs.company', "c")
        ->join('jbs.companyBranch', "cb")
        ->join('jbs.extractions', "ext")
        ->leftJoin("c.email2Companies", "e2c")
        ->leftJoin("jbs.locations", "l")
        ->leftJoin('jbs.extractionKeyword', "e2o")
        ->where("1 = 1");

        $this->addFilterDtoQueries($queryBuilder, $jobOfferFilterDto);
        $this->addJobOfferExtractionQueries($queryBuilder, $jobOfferExtractions);

        $allJobSearchResults = $queryBuilder->getQuery()->execute() ?? [];

        // this limit must be set as last, else it limits the data incorrectly (looks like doctrine bug).
        $queryBuilder->setMaxResults($maxResults);

        return $allJobSearchResults;
    }

    /**
     * Will attempt to find job offer by offer title and company name,
     * Either returns id of the found entity or null if nothing is found.
     *
     * It doesn't care if there are more than one matching entries, it only returns the first found id
     *
     * @param string $jobTitle
     * @param string $companyName
     *
     * @return int|null
     *
     * @throws NonUniqueResultException
     */
    public function findFirstIdByJobTitleAndCompanyName(string $jobTitle, string $companyName): ?int
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jsr.id")
            ->from(JobSearchResult::class, "jsr")
            ->join(Company::class, "c", Join::WITH, "c.id = jsr.company")
            ->where("c.name = :companyName")
            ->andWhere("jsr.jobTitle = :jobTitle")
            ->setParameter("jobTitle", $jobTitle)
            ->setParameter("companyName", $companyName)
            ->setMaxResults(1);

        $id = $queryBuilder->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return $id;
    }

    /**
     * This function is used as solution to the issue where calling the {@see JobSearchResultRepository::findByFilterDto()}
     * is doing ~80k fetches for just ~150-200 offers. This was found out with profiler.
     *
     * Issue is that so many calls cause ~600mb RAM usage and that's just wrong. That might be doctrine based issue.
     * Strangely setting the `max` results solves that problem. This is why this method exists.
     *
     * That solution is VALID because there can NEVER be more offers returned that the count of offers related to give
     * extraction ids.
     *
     * @param JobOfferExtraction[] $jobOfferExtractions
     * @return int
     * @throws Exception
     */
    private function calculateMaxResultsInSearchByFilter(array $jobOfferExtractions): int
    {
        $idsArray = array_map(
            fn(JobOfferExtraction $jobSearchResult) => $jobSearchResult->getId(),
            $jobOfferExtractions,
        );

        $sql = "
            SELECT COUNT(jsr.id) AS counted  
            FROM job_search_result jsr
                
            JOIN job_offer_extraction_job_search_result joejosr
            ON joejosr.job_search_result_id = jsr.id
            
            WHERE joejosr.job_offer_extraction_id IN (:ids)
        ";

        $params = [
            "ids" => $idsArray,
        ];

        $types = [
            "ids" => Connection::PARAM_INT_ARRAY,
        ];

        $maxResults = $this->_em->getConnection()->executeQuery($sql, $params, $types)->fetchOne();

        return $maxResults;
    }

    /**
     * Will deny action if used entity class is {@see JobSearchResult} and not a child such as:
     * - {@see IndeedDeJobSearchResult}
     */
    private function denyForAbstractEntity(JobSearchResult $jobSearchResult = null): void
    {
        if(
                !is_null($jobSearchResult)
            &&  $jobSearchResult::class === JobSearchResult::class
        ){
            throw new BadFunctionCallException("Not allowed to call current function when used entity namespace is: " . JobSearchResult::class);
        }

    }

    /**
     * Will add {@see JobOfferFilterDto} based rules to the {@see QueryBuilder}
     *
     * @param QueryBuilder      $queryBuilder
     * @param JobOfferFilterDto $jobOfferFilterDto
     *
     * @throws DataNotFoundException
     */
    private function addFilterDtoQueries(QueryBuilder $queryBuilder, JobOfferFilterDto $jobOfferFilterDto): void
    {
        BaseFilterModifier::setFilterDto($jobOfferFilterDto);

        MatchMinCompanyYearsOldModifier::apply($queryBuilder);
        MatchPostedDateTimeRangeModifier::apply($queryBuilder);
        MustHaveLocationModifier::apply($queryBuilder);
        MustHaveLanguageDetectedModifier::apply($queryBuilder);
        MatchCountryNameModifier::apply($queryBuilder);
        MatchMinSalaryModifier::apply($queryBuilder);
        MatchMaxSalaryModifier::apply($queryBuilder);
        MustHaveSalaryModifier::apply($queryBuilder);
        MustHaveEmailModifier::apply($queryBuilder);
        MustHavePhoneModifier::apply($queryBuilder);
        MustBeRemoteModifier::apply($queryBuilder);
        MustIncludeCompanyName::apply($queryBuilder);
        MustExcludeCompanyName::apply($queryBuilder);
        MustHaveCountryNameModifier::apply($queryBuilder);
        MustHaveCompanyAgeOldModifier::apply($queryBuilder);
        MustHaveEmployeeCountModifier::apply($queryBuilder);
    }

    /**
     * Will add {@see JobOfferExtraction} based rules to the {@see QueryBuilder}
     * - will basically perform all the necessary joins to not only fetch the offers from the given run
     *   but also all the other offers that were fetched previously, are already in DB and would also be inserted
     *   by current run
     *
     * @param QueryBuilder         $queryBuilder
     * @param JobOfferExtraction[] $jobOfferExtractions
     */
    private function addJobOfferExtractionQueries(QueryBuilder $queryBuilder, array $jobOfferExtractions): void
    {
        $extractionIds = array_map(
            fn(JobOfferExtraction $jobOfferExtraction) => $jobOfferExtraction->getId(),
            $jobOfferExtractions
        );

        $queryBuilder->andWhere("ext.id IN (:extractionIds)")
            ->setParameter("extractionIds", $extractionIds, Connection::PARAM_INT_ARRAY);
    }

    /**
     * Will return all the job offer created date in format of "Y-m-d"
     * In nothing is created on given day then such day is not included in the array
     *
     * @param int $month
     * @param int $year
     *
     * @return array
     */
    public function getAvailableCreatedDate(int $month, int $year): array
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select("
            DATE_FORMAT(jsr.created, '%Y-%m-%d') AS date
        ")->from(JobSearchResult::class, "jsr")
           ->where("DATE_FORMAT(jsr.created, '%Y') = :year")
           ->andWhere("DATE_FORMAT(jsr.created, '%m') = :month")
           ->groupBy("date")
           ->setParameter("year", $year, Types::INTEGER)
           ->setParameter("month", $month, Types::INTEGER)
           ->orderBy("jsr.created", "DESC");

        $results           = $qb->getQuery()->setHydrationMode(AbstractQuery::HYDRATE_ARRAY)->execute();
        $normalizedResults = array_column($results, 'date');

        return $normalizedResults;
    }

    /**
     * Will check if given offer is included in any earlier user extractions
     *
     * @param JobOfferExtraction $handledExtraction
     * @param JobSearchResult    $jobOffer
     * @param array              $userAllExtractionIds
     *
     * @return bool
     */
    public function isOfferBoundToEarlierExtractions(
        JobOfferExtraction $handledExtraction,
        JobSearchResult    $jobOffer,
        array              $userAllExtractionIds
    ): bool
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jsr")
            ->from(JobSearchResult::class, "jsr")
            ->join("jsr.extractions", "ex")
            ->where("ex.created < :beforeDate")
            ->andWhere("jsr.id = :offerId")
            ->andWhere("ex.id IN (:extractionIds)")
            ->setParameter('beforeDate', $handledExtraction->getCreated()->format("Y-m-d H:i:s"))
            ->setParameter("offerId", $jobOffer->getId())
            ->setParameter("extractionIds", $userAllExtractionIds);

        $results = $queryBuilder->getQuery()->execute();
        return !empty($results);
    }

    /**
     * @param int   $maxDaysOffset
     * @param array $extractionIds
     *
     * @return JobSearchResult[]
     */
    public function findAllCreatedInDaysOffset(int $maxDaysOffset, array $extractionIds = []): array
    {
        $minDate = (new DateTime())->modify("-{$maxDaysOffset} DAY")->format("Y-m-d H:i:s");

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("jbs")
             ->from(JobSearchResult::class, "jbs")
             ->where("jbs.created >= :minDate")
             ->setParameter("minDate", $minDate)
             ->orderBy("jbs.created", "ASC");

        if (!empty($extractionIds)) {
            $queryBuilder->innerJoin("jbs.extractions", "ex")
                         ->andWhere("ex.id IN (:extractionIds)")
                         ->setParameter("extractionIds", $extractionIds);
        }

        return $queryBuilder->getQuery()->execute();
    }

}
