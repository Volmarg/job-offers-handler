<?php

namespace JobSearcher\Service\JobService\Resolver\API\Fr;

use Exception;
use GeoTool\Service\CountryCode\FrenchDivisionCode;
use JobSearcher\Service\Env\EnvReader;
use JobSearcher\Service\JobSearch\Keyword\KeywordHandlerService;
use JobSearcher\Service\JobService\Resolver\JobServiceResolver;
use JobSearcher\Service\JobService\Resolver\Traits\BaseSearchUriConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\DetailPageAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\KeywordsAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\LocationNameAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MainConfigurationDtoAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\MaxPaginationPagesAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\PageNumberAwareTrait;
use JobSearcher\Service\JobService\Resolver\Traits\SearchUriAwareTrait;
use JobSearcher\Service\Validation\ValidatorService;
use WebScrapperBundle\Service\Request\Guzzle\GuzzleService;

/**
 * Handles resolving data for service {@link https://www.apec.fr/}, yaml file: `apec.fryaml`
 */
class ApecFrResolver extends JobServiceResolver
{
    use KeywordsAwareTrait;
    use SearchUriAwareTrait;
    use LocationNameAwareTrait;
    use PageNumberAwareTrait;
    use DetailPageAwareTrait;
    use MaxPaginationPagesAwareTrait;
    use MainConfigurationDtoAwareTrait;
    use BaseSearchUriConfigurationDtoAwareTrait;

    private const DETAIL_PAGE_PARAM_NAME_OFFER_ID = "numeroOffre";

    /**
     * Handles building search uri
     *
     * @param array $parameters
     *
     * @return string
     * @throws Exception
     */
    public function buildSearchUri(array $parameters): string
    {
        return $this->getSearchUri($parameters);
    }

    /**
     * @throws Exception
     */
    public function providePaginationLocationName(array $parameters): array
    {
        $frenchDivisionService = $this->kernel->getContainer()->get(FrenchDivisionCode::class);
        $locationName          = $this->getLocationName($parameters);
        if (empty($locationName)) {
            return [];
        }

        $locationId = $frenchDivisionService->getDivNumber($locationName);
        if (empty($locationId)) {
            throw new Exception("Could not get the division / location id for location: {$locationId}");
        }

        return [$locationId];
    }

    /**
     * @param array $parameters
     *
     * @return int
     */
    public function provideMaxResultsPerPage(array $parameters): int
    {
        $mainConfigDto = $this->getMainConfigurationDto($parameters);
        $maxPagination = $this->getMaxPaginationPages($parameters);
        $maxPagination = ($maxPagination === 0 ? 1 : $maxPagination);
        $maxPageSize   = ($maxPagination * $mainConfigDto->getSearchUriConfigurationDto()->getPaginationIncrementValue());

        return $maxPageSize;
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function providePaginationKeywords(array $parameters): string
    {
        $baseSearchUriDto = $this->getMainConfigurationDto($parameters)->getSearchUriConfigurationDto();
        return KeywordHandlerService::glueAll(
            $this->getKeywords($parameters),
            $baseSearchUriDto->isEncodeQuery(),
            $baseSearchUriDto->getMultipleKeyWordsSeparatorCharacter(),
            $baseSearchUriDto->getPaginationSpacebarInKeywordWordsReplaceCharacter(),
        );
    }

    /**
     * @param array $parameters
     *
     * @return array
     *
     * @throws Exception
     */
    public function buildDetailPageDataArray(array $parameters): array
    {
        $calledUri   = $this->getDetailPageUrl($parameters); // this is the API url
        $modifiedUri = $calledUri . "W";

        $validator     = $this->kernel->getContainer()->get(ValidatorService::class);
        $guzzleService = $this->kernel->getContainer()->get(GuzzleService::class);
        $guzzleService->setIsWithProxy(EnvReader::isProxyEnabled());

        $guzzleResponse = $guzzleService->get($modifiedUri);
        $json           = $guzzleResponse->getBody()->getContents();
        if (!$validator->validateJson($json)) {
            throw new Exception("Response from detail page of apec fr. is not a json!");
        }

        $data = json_decode($json, true);
        return [
            "detail" => [
                'description' => $this->getDescriptionContent($data),
                'url'         => $this->buildDetailPageUrl($parameters, $data),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        // TODO: Implement init() method.
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function getDescriptionContent(array $data): string
    {
        $desc                = "";
        $spacer              = "<br/><br/>";
        $texteHtml           = $data['texteHtml'] ?? null;
        $texteHtmlProfil     = $data['texteHtmlProfil'] ?? null;
        $texteHtmlEnterprise = $data['texteHtmlEntreprise'] ?? null;

        if (!empty($texteHtml)) {
            $desc .= $texteHtml . $spacer;
        }

        if (!empty($texteHtmlProfil)) {
            $desc .= $texteHtmlProfil . $spacer;
        }

        if (!empty($texteHtmlEnterprise)) {
            $desc .= $texteHtmlEnterprise . $spacer;
        }

        return $desc;
    }

    /**
     * That's the url that user will be visiting in the browser.
     *
     * @param array $parameters
     * @param array $data
     *
     * @return string
     *
     * @throws Exception
     */
    private function buildDetailPageUrl(array $parameters, array $data): string
    {
        $offerId = $data['id'] ?? null;
        if (empty($offerId)) {
            throw new Exception("Could not extract offer id from the detail page response data");
        }

        return $this->getMainConfigurationDto($parameters)->getHost() . "/candidat/recherche-emploi.html/emploi/detail-offre/" . $offerId . "W";
    }
}