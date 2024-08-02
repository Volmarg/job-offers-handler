<?php

namespace JobSearcher\Action\API\Statistic\Offers;

use Exception;
use JobSearcher\Repository\Statistic\JobSearchResultsStatisticRepository;
use JobSearcher\Response\Statistic\Offers\GetCountOfUniquePerDayResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api/statistic/offers/", name: "api.statistic.offers.")]
class OffersStatisticAction extends AbstractController
{
    public function __construct(
        private readonly JobSearchResultsStatisticRepository $jobSearchResultsStatisticRepository
    ){}

    /**
     * Returns response with count of unique found offers per day in month of year
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route("count-of-unique-per-day", name: "count_of_unique_per_day", methods: [Request::METHOD_POST])]
    public function getCountOfUniquePerDay(Request $request): JsonResponse
    {
        $json = $request->getContent();
        $data = json_decode($json, true);

        $extractionIds = $data["extractionIds"] ?? [];
        $response      = GetCountOfUniquePerDayResponse::buildOkResponse();
        $result        = $this->jobSearchResultsStatisticRepository->getCountOfUniquePerDay($extractionIds);

        $response->setDtos($result);

        return $response->toJsonResponse();
    }
}