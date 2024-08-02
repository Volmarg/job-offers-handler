<?php

namespace JobSearcher\Action\API\Statistic;


use JobSearcher\Repository\Statistic\ContactEmailStatisticRepository;
use JobSearcher\Service\Statistic\ContactEmailStatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StatisticAction extends AbstractController
{

    public function __construct(
        private readonly ContactEmailStatisticService    $contactEmailStatisticService,
        private readonly ContactEmailStatisticRepository $contactEmailStatisticRepository
    ){}

    #[Route("/api/statistic/contact-email", name: "api.statistic.contact.email", methods: [Request::METHOD_GET])]
    public function contactEmail(): never
    {
        $this->contactEmailStatisticService->getCountOfOffersWithEmails(6, 2022);
    }

    #[Route("/api/statistic/offers-without-email", name: "api.statistic.offers.without.email", methods: [Request::METHOD_GET])]
    public function offersWithoutEmail(): never
    {
        $result = $this->contactEmailStatisticRepository->getOffersWithoutEmails(6, 2022);
    }
}