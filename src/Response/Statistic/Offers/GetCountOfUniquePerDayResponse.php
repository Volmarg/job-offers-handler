<?php

namespace JobSearcher\Response\Statistic\Offers;

use JobSearcher\Action\API\Statistic\Offers\OffersStatisticAction;
use JobSearcher\DTO\Statistic\JobSearch\CountOfUniquePerDayDto;
use JobSearcher\Response\BaseApiResponse;

/**
 * Related to the {@see OffersStatisticAction::getCountOfUniquePerDay()}
 */
class GetCountOfUniquePerDayResponse extends BaseApiResponse
{
    /**
     * @var CountOfUniquePerDayDto[]
     */
    private array $dtos;

    /**
     * @param array $dtos
     */
    public function setDtos(array $dtos): void
    {
        $this->dtos = $dtos;
    }

    /**
     * @return array
     */
    public function getDtos(): array
    {
        return $this->dtos;
    }

}