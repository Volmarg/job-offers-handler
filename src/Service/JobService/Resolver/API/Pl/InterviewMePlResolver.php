<?php

namespace JobSearcher\Service\JobService\Resolver\API\Pl;

use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\DetailPageAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MainConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MaxPaginationPagesAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;

/**
 * Handles resolving data for service {@link https://interviewme.pl/}, yaml file: `interviewme.pl.yaml`
 */
class InterviewMePlResolver extends JobServiceResolver
{
    use MainConfigurationDtoAwareTrait;
    use KeywordsAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use DetailPageAwareTrait;
    use MaxPaginationPagesAwareTrait;

    private const RESULTS_PER_PAGE = 30;

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     */
    public function buildSearchUri(array $parameters): string
    {
        $searchUri = $this->getSearchUri($parameters);

        return $searchUri;
    }

    public function getQuery(): string
    {
        return 'mutation getSearchedJobs($input: BOLDJobServiceServiceModelApiStoredJobsQueryParamInput!) {tresult: getStoredJobs(bOLDJobServiceServiceModelApiStoredJobsQueryParamInput: $input)}';
    }

    public function getOperationName(): string
    {
        return "getSearchedJobs";
    }

    public function getVariablesInput( array $parameters): array
    {
        $maxPagination = $this->getMaxPaginationPages($parameters);
        $maxPagination = ($maxPagination === 0 ? 1 : $maxPagination);
        $maxPageSize   = ($maxPagination * self::RESULTS_PER_PAGE);

        $baseSearchUriDto = $this->getMainConfigurationDto($parameters)->getSearchUriConfigurationDto();
        $locationName     = $this->getLocationName($parameters);
        $gluedKeywords    = KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriDto->isEncodeQuery(),
            $baseSearchUriDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );

        $returnedData = [
            "query"       => $gluedKeywords,
            "pageNumber"  => 1,
            "pageSize"    => $maxPageSize,
            "jobAge"      => 900,
            "sortOrder"   => "Date",
            "countryCode" => "PL",
        ];

        if (!empty($locationName)) {
            $returnedData['location'] = $locationName;
        }

        return $returnedData;
    }


    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        // TODO: Implement init() method.
    }
}