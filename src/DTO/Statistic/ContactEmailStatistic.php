<?php

namespace JobSearcher\DTO\Statistic;

use DateTime;

/**
 * DTO for contact email statistic
 */
class ContactEmailStatistic
{
    public function __construct(
        private readonly DateTime $dateTime,
        private readonly int      $countOfferWithoutEmail,
        private readonly int      $countOfferWithEmail,
        private readonly float    $percentOfferWithoutEmail,
        private readonly float    $percentOfferWithEmail,
    ) {}

    /**
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * @return int
     */
    public function getCountOfferWithoutEmail(): int
    {
        return $this->countOfferWithoutEmail;
    }

    /**
     * @return int
     */
    public function getCountOfferWithEmail(): int
    {
        return $this->countOfferWithEmail;
    }

    /**
     * @return float
     */
    public function getPercentOfferWithoutEmail(): float
    {
        return $this->percentOfferWithoutEmail;
    }

    /**
     * @return float
     */
    public function getPercentOfferWithEmail(): float
    {
        return $this->percentOfferWithEmail;
    }

}