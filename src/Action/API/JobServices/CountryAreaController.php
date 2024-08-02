<?php

namespace JobSearcher\Action\API\JobServices;

use Exception;
use JobSearcher\Response\Offer\GetSupportedAreasResponse;
use JobSearcher\Service\JobService\ConfigurationReader\ConfigurationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Logic related to providing the supported country / areas for which offers search can be handled for
 */
#[Route("/api", name: "api.")]
class CountryAreaController extends AbstractController
{
    public function __construct(
        private readonly ConfigurationReader $configurationReader
    ){}

    /**
     * Returns the supported areas iso codes, where one area is not an iso code yet has to be returned
     * that's "global" because some services got offers mixed, not targeted to specific countries
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/country-area/get-supported-areas", name: "country.area.get.supported.areas", methods: [Request::METHOD_GET])]
    public function getSupportedAreas(): JsonResponse
    {
        $areas     = $this->configurationReader->getSupportedCountries();
        $areaNames = array_values($areas);

        $response = GetSupportedAreasResponse::buildOkResponse();
        $response->setAreaNames($areaNames);

        return $response->toJsonResponse();
    }
}