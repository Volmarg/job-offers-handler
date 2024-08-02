<?php

namespace JobSearcher\DTO\Statistic\JobSearch;

class CountOfUniquePerDayDto
{
    public function __construct(
        private readonly int $offersCount,
        private readonly int $year,
        private readonly int $month,
        private readonly int $day,
        private readonly int $extractionId
    ) {
    }

    /**
     * @return int
     */
    public function getOffersCount(): int
    {
        return $this->offersCount;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @return int
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * @return int
     */
    public function getExtractionId(): int
    {
        return $this->extractionId;
    }

}