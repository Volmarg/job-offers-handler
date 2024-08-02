<?php

namespace JobSearcher\Service\Symfony;

use Psr\Log\LoggerInterface;
use Throwable;

class LoggerService
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {

    }

    /**
     * @param Throwable $e
     * @param array      $data
     */
    public function logException(Throwable $e, array $data = []): void
    {
        $this->logger->critical("Exception was thrown", [
            "class"     => self::class,
            "exception" => [
                "trace"   => $e->getTraceAsString(),
                "message" => $e->getMessage(),
            ],
            'data' => $data,
        ]);
    }
}