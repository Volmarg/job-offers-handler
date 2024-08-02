<?php
namespace JobSearcher\Service\Statistic;

use DateTime;
use Exception;
use JobSearcher\DTO\Statistic\ContactEmailStatistic;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Repository\Statistic\ContactEmailStatisticRepository;

/**
 * Contains statistic regarding the company / offer contact emails
 */
class ContactEmailStatisticService
{
    public function __construct(
        private readonly ContactEmailStatisticRepository $contactEmailStatisticRepository,
        private readonly JobSearchResultRepository       $jobSearchResultRepository
    ){}

    /**
     * @return ContactEmailStatistic[]
     * @throws Exception
     */
    public function getCountOfOffersWithEmails(int $month, int $year): array
    {
        $offersWithoutContactEmail = $this->contactEmailStatisticRepository->countOffersWithoutApplicationEmail($month, $year);
        $offersWithContactEmail    = $this->contactEmailStatisticRepository->countOffersWithApplicationEmail($month, $year);
        $availableCreatedDate      = $this->jobSearchResultRepository->getAvailableCreatedDate($month, $year);

        $statistics = [];
        foreach ($availableCreatedDate as $date) {
            $countOfferWithEmailForDate    = $offersWithoutContactEmail[$date] ?? 0;
            $countOfferWithoutEmailForDate = $offersWithContactEmail[$date]    ?? 0;
            $allOfferCount                 = $countOfferWithEmailForDate + $countOfferWithoutEmailForDate;

            $percentOffersWithEmail    = (
                $countOfferWithEmailForDate === 0 ? 100 : (
                    100 - $countOfferWithEmailForDate / $allOfferCount * 100
                )
            );

            $percentOffersWithoutEmail = 100 - $percentOffersWithEmail;

            $dateTime     = new DateTime($date);
            $statistics[] = new ContactEmailStatistic(
                $dateTime,
                $countOfferWithEmailForDate,
                $countOfferWithoutEmailForDate,
                round($percentOffersWithoutEmail, 2),
                round($percentOffersWithEmail, 2)
            );
        }

        return $statistics;
    }
}