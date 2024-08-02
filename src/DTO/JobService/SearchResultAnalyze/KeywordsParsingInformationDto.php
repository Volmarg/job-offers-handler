<?php

namespace JobSearcher\DTO\JobService\SearchResultAnalyze;

/**
 * Represent result of parsing keywords with any kind of logic
 */
class KeywordsParsingInformationDto
{

    /**
     * @var string $stringAfterApplyingKeywords
     */
    private string $stringAfterApplyingKeywords = "";

    /**
     * @var array $countOfKeywords
     */
    private array $countOfKeywords = [];

    /**
     * @return string
     */
    public function getStringAfterApplyingKeywords(): string
    {
        return $this->stringAfterApplyingKeywords;
    }

    /**
     * @param string $stringAfterApplyingKeywords
     */
    public function setStringAfterApplyingKeywords(string $stringAfterApplyingKeywords): void
    {
        $this->stringAfterApplyingKeywords = $stringAfterApplyingKeywords;
    }

    /**
     * @return array
     */
    public function getCountOfKeywords(): array
    {
        return $this->countOfKeywords;
    }

    /**
     * @param array $countOfKeywords
     */
    public function setCountOfKeywords(array $countOfKeywords): void
    {
        $this->countOfKeywords = $countOfKeywords;
    }

}