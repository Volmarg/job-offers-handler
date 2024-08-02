<?php

namespace JobSearcher\Action\API;

use JobSearcher\Response\BaseApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api", name: "api.")]
class PingController
{
    /**
     * Simple bing method
     *
     * @return JsonResponse
     */
    #[Route("/ping", name: "ping", methods: [Request::METHOD_GET])]
    public function doPing(): JsonResponse
    {
        return BaseApiResponse::buildOkResponse()->toJsonResponse();
    }
}