<?php

namespace JobSearcher\DTO\JobService\SearchResult;

/**
 * Contain job detail information
 */
class JobDetailDto
{

    private const KEY_JOB_TITLE       = 'jobTitle';
    private const KEY_JOB_DESCRIPTION = 'jobDescription';

    /**
     * @var string $jobTitle
     */
    private string $jobTitle = "";

    /**
     * @var string $jobDescription
     */
    private string $jobDescription = "";

    /**
     * @return string
     */
    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    /**
     * @param string $jobTitle
     */
    public function setJobTitle(string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    /**
     * @return string
     */
    public function getJobDescription(): string
    {
        return $this->jobDescription;
    }

    /**
     * @param string $jobDescription
     */
    public function setJobDescription(string $jobDescription): void
    {
        $this->jobDescription = $jobDescription;
    }

    /**
     * Return array representation of the dto
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::KEY_JOB_TITLE       => $this->getJobTitle(),
            self::KEY_JOB_DESCRIPTION => $this->getJobDescription(),
        ];
    }

}