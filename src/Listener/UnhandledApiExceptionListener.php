<?php

namespace JobSearcher\Listener;

use JobSearcher\Response\BaseApiResponse;
use JobSearcher\Security\LexitBundleJwtTokenAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handler for api based exception
 * @package App\Listener
 */
class UnhandledApiExceptionListener implements EventSubscriberInterface
{

    public function __construct(
        private readonly LoggerInterface $logger
    ){}

    /**
     * @param ExceptionEvent $event
     */
    public function onException(ExceptionEvent $event)
    {
        if (!str_contains($event->getRequest()->getRequestUri(), LexitBundleJwtTokenAuthenticator::API_STRING_MATCH)) {
            return;
        }
        if (
                $event->getThrowable() instanceof NotFoundHttpException
            &&  str_starts_with($event->getThrowable()->getMessage(), "No route found")
        ) {
            $message = "Either wrong request type or target uri does not exist";
            $event->setResponse(BaseApiResponse::buildBadRequestErrorResponse($message)->toJsonResponse());
            return;
        }

        $this->logger->critical("Unhandled exception for api call", [
            "exception" => [
                "class"   => $event->getThrowable()::class,
                "message" => $event->getThrowable()->getMessage(),
                "trace"   => $event->getThrowable()->getTraceAsString(),
            ]
        ]);

        $event->setResponse(BaseApiResponse::buildInternalServerErrorResponse()->toJsonResponse());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException'
        ];
    }
}