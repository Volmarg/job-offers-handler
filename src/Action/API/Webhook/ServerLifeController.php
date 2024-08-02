<?php

namespace JobSearcher\Action\API\Webhook;

use JobSearcher\Response\BaseApiResponse;
use JobSearcher\Service\Server\ServerLifeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * {@see ServerLifeService}
 */
#[Route("/api/webhook/", name: "api.webhook.")]
class ServerLifeController extends AbstractController
{
    public function __construct(
        private readonly ServerLifeService $serverLifeService
    ) {

    }

    /**`
     * This route will just call {@see ServerLifeService::createShutdownFile()}
     *
     * @return JsonResponse
     */
    #[Route("create-shutdown-file", name: "create.shutdown-file", methods: [Request::METHOD_POST])]
    public function createShutdownFile(): JsonResponse
    {
        $this->serverLifeService->createShutdownFile();
        return BaseApiResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * This route will just call {@see ServerLifeService::createRestartFile()}
     *
     * @return JsonResponse
     */
    #[Route("create-restart-file", name: "create.restart-file", methods: [Request::METHOD_POST])]
    public function createRestartFile(): JsonResponse
    {
        $this->serverLifeService->createRestartFile();
        return BaseApiResponse::buildOkResponse()->toJsonResponse();
    }

}
