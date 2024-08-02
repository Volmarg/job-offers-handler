<?php

namespace JobSearcher\DTO\JobService\SearchFilter\SubFilters;

use Exception;

/**
 * Used for setting up filter for job posted date time. Allows searching for job posted:
 * - between 2 date-times,
 * - date-time bigger than X
 * - date-time lesser than X
 */
class JobPostedDateTimeFilterDto
{

    const ALLOWED_JOB_OFFER_POSTED_DATE_TIME_OPERATORS = [
        ">", "<", "="
    ];

    /**
     * @var string|null $firstTimestamp
     */
    private ?string $firstTimestamp = null;

    /**
     * @var string|null $secondTimestamp
     */
    private ?string $secondTimestamp = null;

    /**
     * @var string $comparisonOperator
     */
    private string $comparisonOperator = "=";

    /**
     * @return string|null
     */
    public function getFirstTimestamp(): ?string
    {
        return $this->firstTimestamp;
    }

    /**
     * @param string|null $firstTimestamp
     */
    public function setFirstTimestamp(?string $firstTimestamp): void
    {
        $this->firstTimestamp = $firstTimestamp;
    }

    /**
     * @return string|null
     */
    public function getSecondTimestamp(): ?string
    {
        return $this->secondTimestamp;
    }

    /**
     * @param string|null $secondTimestamp
     */
    public function setSecondTimestamp(?string $secondTimestamp): void
    {
        $this->secondTimestamp = $secondTimestamp;
    }

    /**
     * @return string
     */
    public function getComparisonOperator(): string
    {
        return $this->comparisonOperator;
    }

    /**
     * @param string $comparisonOperator
     */
    public function setComparisonOperator(string $comparisonOperator): void
    {
        $this->comparisonOperator = $comparisonOperator;
    }

    /**
     * Will validate the state of the dto
     * @throws Exception
     */
    public function validateSelf()
    {
        if( !in_array($this->comparisonOperator, self::ALLOWED_JOB_OFFER_POSTED_DATE_TIME_OPERATORS) ){
            throw new Exception("Give compare operator is not valid! Got: {$this->comparisonOperator}");
        }
    }

}