<?php

namespace JobSearcher\Service\Server;

use DateTime;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Handles creating special files which can be used by cron to do something with server
 * The idea of this service was that if something will go VERY wrong like for example
 * so hole in the code will cause the paid api to be called thousands of times then this
 * will be a way to tell the server to "go down" and stop generating costs.
 */
class ServerLifeService
{
    private const MAX_WAIT_TIME_MINUTES = 2;

    public function __construct(
        private readonly string $shutdownFilePath,
        private readonly string $restartFilePath,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Creates a file which can be used by cron to bring the server down
     */
    public function createShutdownFile(): void
    {
        if (!file_exists($this->shutdownFilePath)) {
            file_put_contents($this->shutdownFilePath, "");
        }

        $this->emergencyNotWorking(__FUNCTION__, $this->shutdownFilePath);
    }

    /**
     * Creates a file which can be used by cron to restart the server
     */
    public function createRestartFile(): void
    {
        if (!file_exists($this->restartFilePath)) {
            file_put_contents($this->restartFilePath, "");
        }

        $this->emergencyNotWorking(__FUNCTION__, $this->restartFilePath);
    }

    /**
     * Send emergency E-Mail when nothing will happen with the server between the moment when file was created and
     * NOW() - {@see ServerLifeService::MAX_WAIT_TIME_MINUTES}
     *
     * @param string $calledFunction
     * @param string $handlerFilePath
     */
    private function emergencyNotWorking(string $calledFunction, string $handlerFilePath): void
    {
        if (!file_exists($handlerFilePath)) {
            return;
        }

        $file            = new File($handlerFilePath);
        $nowStamp        = (new DateTime())->getTimestamp();
        $fileCreatedDate = (new DateTimeImmutable())
            ->setTimestamp($file->getMTime());

        $maxFileCreatedTimestamp = $fileCreatedDate->modify("+" . self::MAX_WAIT_TIME_MINUTES . " MINUTE")
            ->getTimestamp();

        if ($maxFileCreatedTimestamp >= $nowStamp) {
            return;
        }

        $this->logger->emergency("Logic of " . self::class . " is not working. This is real EMERGENCY, do something NOW!!", [
            'calledFunction' => $calledFunction,
            'fileCreatedDate' => $fileCreatedDate->format("Y-m-d h:i:s")
        ]);
    }
}