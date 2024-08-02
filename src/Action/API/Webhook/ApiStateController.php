<?php

namespace JobSearcher\Action\API\Webhook;

use Exception;
use JobSearcher\Entity\Service\ServiceState;
use JobSearcher\Repository\Service\ServiceStateRepository;
use JobSearcher\Response\BaseApiResponse;
use JobSearcher\Service\Api\ApiStateService;
use JobSearcher\Service\Server\ServerLifeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api/webhook/", name: "api.webhook.")]
class ApiStateController extends AbstractController
{

    public function __construct(
        private readonly ApiStateService $apiStateService
    ){}

    /**
     * Will turn given api state to DISABLED, so this way it should be softly disabled from calling:
     * - {@see ApiStateService} / {@see ServiceState} / {@see ServiceStateRepository}
     *
     * This however will NOT prevent endless loops (generally framework should kill itself when this happens),
     * for such cases there is {@see ServerLifeService::createShutdownFile()}
     *
     * @param string $apiName
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    #[Route("disable-api/{apiName}", name: "disable.api", methods: [Request::METHOD_POST])]
    public function disableApi(string $apiName): JsonResponse
    {
        $this->apiStateService->disableApiService($apiName);

        return BaseApiResponse::buildOkResponse()->toJsonResponse();
    }
}
