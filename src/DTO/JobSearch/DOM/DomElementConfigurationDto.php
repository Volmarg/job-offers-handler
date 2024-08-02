<?php

namespace JobSearcher\DTO\JobSearch\DOM;

/**
 * This DTO represents dom element configuration necessary to get the target DOM element
 */
class DomElementConfigurationDto
{
    // All of these PURPOSE const are being used in the yaml files
    const PURPOSE_DETAIL_PAGE_OFFER_DETAIL_JOB_NAME             = "DETAIL_PAGE_OFFER_DETAIL_JOB_NAME";
    const PURPOSE_DETAIL_PAGE_OFFER_DETAIL_JOB_DESCRIPTION      = "DETAIL_PAGE_OFFER_DETAIL_JOB_DESCRIPTION";
    const PURPOSE_DETAIL_PAGE_OFFER_DETAIL_JOB_POSTED_DATE_TIME = "DETAIL_PAGE_OFFER_DETAIL_JOB_POSTED_DATE_TIME";

    const PURPOSE_DETAIL_PAGE_COMPANY_WORKPLACE_DATA_LOCATION     = "DETAIL_PAGE_COMPANY_WORKPLACE_DATA_LOCATION";
    const PURPOSE_DETAIL_PAGE_COMPANY_WORKPLACE_DATA_COMPANY_NAME = "DETAIL_PAGE_COMPANY_WORKPLACE_DATA_COMPANY_NAME";

    const PURPOSE_DETAIL_PAGE_CONTACT_DETAIL_COMPANY_EMAIL = "DETAIL_PAGE_CONTACT_DETAIL_COMPANY_EMAIL";
    const PURPOSE_DETAIL_PAGE_CONTACT_DETAIL_COMPANY_PHONE = "DETAIL_PAGE_CONTACT_DETAIL_COMPANY_PHONE";

    const PURPOSE_DETAIL_PAGE_SALARY_MIN           = "DETAIL_PAGE_SALARY_MIN";
    const PURPOSE_DETAIL_PAGE_SALARY_MAX           = "DETAIL_PAGE_SALARY_MAX";
    const PURPOSE_DETAIL_PAGE_SALARY_ESTIMATED     = "DETAIL_PAGE_SALARY_ESTIMATED";
    const PURPOSE_DETAIL_PAGE_REMOTE_WORK_POSSIBLE = "DETAIL_PAGE_REMOTE_WORK_POSSIBLE";

    const PURPOSE_PAGINATION_PAGE_LINK_TO_DETAIL_PAGE = "PAGINATION_PAGE_LINK_TO_DETAIL_PAGE";
    const PURPOSE_PAGINATION_PAGE_COMPANY_NAME        = "PAGINATION_PAGE_COMPANY_NAME";
    const PURPOSE_PAGINATION_PAGE_JOB_TITLE           = "PAGINATION_PAGE_JOB_TITLE";
    const PURPOSE_PAGINATION_PAGE_JOB_DESCRIPTION     = "PAGINATION_PAGE_JOB_DESCRIPTION";
    const PURPOSE_PAGINATION_PAGE_OFFER_BLOCK         = "PAGINATION_PAGE_OFFER_BLOCK";
    const PURPOSE_PAGINATION_PAGE_COMPANY_LOCATION    = "PAGINATION_PAGE_COMPANY_LOCATION";
    const PURPOSE_PAGINATION_POSTED_DATE              = "PAGINATION_POSTED_DATE";
    const PURPOSE_PAGINATION_NO_RESULTS               = "PAGINATION_NO_RESULTS";
    public const PURPOSE_PAGINATION_VALID_LOCATION_RESULTS = "PAGINATION_VALID_LOCATION_RESULTS";

    /**
     * @var string $domElementPurpose
     */
    private string $domElementPurpose;

    /**
     * Selector to get the desired dom element
     *
     * @var ?string $cssSelector
     */
    private ?string $cssSelector;

    /**
     * @var ?string $targetAttributeName
     */
    private ?string $targetAttributeName;

    /**
     * @var bool $getDataFromInnerText
     */
    private bool $getDataFromInnerText;

    /**
     * @var bool $getDataFromAttribute
     */
    private bool $getDataFromAttribute;

    /**
     * @var string|null $calledMethodName
     */
    private ?string $calledMethodName;

    public function __construct(
        string  $domElementPurpose,
        ?string  $cssSelector,
        ?string  $targetAttributeName,
        bool    $getDataFromInnerText,
        bool    $getDataFromAttribute,
        ?string $calledMethodName,
        private readonly bool $dataFromInnerTextWithHtml,
        private readonly array $removedElementsSelectors,
        private readonly ?string $iframeCssSelector,
        private readonly array $calledMethodArgs
    ){
        $this->domElementPurpose    = $domElementPurpose;
        $this->cssSelector          = $cssSelector;
        $this->targetAttributeName  = $targetAttributeName;
        $this->getDataFromInnerText = $getDataFromInnerText;
        $this->getDataFromAttribute = $getDataFromAttribute;
        $this->calledMethodName     = $calledMethodName;
    }

    /**
     * @return string
     */
    public function getDomElementPurpose(): string
    {
        return $this->domElementPurpose;
    }

    /**
     * @param string $domElementPurpose
     */
    public function setDomElementPurpose(string $domElementPurpose): void
    {
        $this->domElementPurpose = $domElementPurpose;
    }

    /**
     * @return string|null
     */
    public function getCssSelector(): ?string
    {
        return $this->cssSelector;
    }

    /**
     * @param string|null $cssSelector
     */
    public function setCssSelector(?string $cssSelector): void
    {
        $this->cssSelector = $cssSelector;
    }

    /**
     * @return string|null
     */
    public function getTargetAttributeName(): ?string
    {
        return $this->targetAttributeName;
    }

    /**
     * @param string|null $targetAttributeName
     */
    public function setTargetAttributeName(?string $targetAttributeName): void
    {
        $this->targetAttributeName = $targetAttributeName;
    }

    /**
     * @return bool
     */
    public function isGetDataFromInnerText(): bool
    {
        return $this->getDataFromInnerText;
    }

    /**
     * @param bool $getDataFromInnerText
     */
    public function setGetDataFromInnerText(bool $getDataFromInnerText): void
    {
        $this->getDataFromInnerText = $getDataFromInnerText;
    }

    /**
     * @return bool
     */
    public function isGetDataFromAttribute(): bool
    {
        return $this->getDataFromAttribute;
    }

    /**
     * @param bool $getDataFromAttribute
     */
    public function setGetDataFromAttribute(bool $getDataFromAttribute): void
    {
        $this->getDataFromAttribute = $getDataFromAttribute;
    }

    /**
     * @return string|null
     */
    public function getCalledMethodName(): ?string
    {
        return $this->calledMethodName;
    }

    /**
     * @param string|null $calledMethodName
     */
    public function setCalledMethodName(?string $calledMethodName): void
    {
        $this->calledMethodName = $calledMethodName;
    }

    /**
     * @return bool
     */
    public function isDataFromInnerTextWithHtml(): bool
    {
        return $this->dataFromInnerTextWithHtml;
    }

    /**
     * @return array
     */
    public function getRemovedElementsSelectors(): array
    {
        return $this->removedElementsSelectors;
    }

    /**
     * @return string|null
     */
    public function getIframeCssSelector(): ?string
    {
        return $this->iframeCssSelector;
    }

    /**
     * @return array
     */
    public function getCalledMethodArgs(): array
    {
        return $this->calledMethodArgs;
    }

}