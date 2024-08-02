<?php

namespace JobSearcher\RabbitMq\Consumer\JobSearch;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Command\JobSearch\AbstractJobSearchCommand;
use JobSearcher\Command\JobSearch\AllJobOffersExtractorCommand;
use JobSearcher\Constants\RabbitMq\Consumer\JobSearch\DoJobSearchConsumerConstants;
use JobSearcher\DTO\JobSearch\JobSearchParameterBag;
use JobSearcher\Entity\Extraction\Extraction2AmqpRequest;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\Storage\AmqpStorage;
use JobSearcher\RabbitMq\Consumer\BaseConsumer;
use JobSearcher\Repository\Extraction\Extraction2AmqpRequestRepository;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use JobSearcher\Service\Bundle\Jooblo\JoobloService;
use JobSearcher\Service\RabbitMq\AmqpService;
use JobSearcher\Service\RabbitMq\JobSearcher\JobSearchDoneProducerService;
use JobSearcher\Service\Shell\Command\ShellCommandService;
use JobSearcher\Service\Storage\AmqpStorageService;
use JobSearcher\Service\Validation\ValidatorService;
use LogicException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Handles calling the {@see AllJobOffersExtractorCommand}
 */
class DoJobSearchConsumer extends BaseConsumer
{
    public function __construct(
        private readonly ShellCommandService          $shellCommandService,
        private readonly JobSearchDoneProducerService $jobSearchDoneProducerService,
        private readonly AmqpService                  $amqpService,
        private readonly ValidatorService             $validatorService,
        protected LoggerInterface                     $amqpLogger,
        AmqpStorageService                            $amqpStorageService,
        EntityManagerInterface                        $entityManager,
        private readonly JobOfferExtractionRepository $jobOfferExtractionRepository,
        private readonly KernelInterface              $kernel,
        private readonly JoobloService                $joobloService
    )
    {
        parent::__construct($amqpStorageService, $entityManager, $amqpLogger);
    }

    /**
     * @param AMQPMessage $msg
     * @param AmqpStorage $amqpStorageEntity
     *
     * @return int
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function doExecute(AMQPMessage $msg, AmqpStorage $amqpStorageEntity): int
    {
        if ($this->joobloService->isSystemDisabled()) {
            sleep(300); //5 min, to prevent sending the message back and forth in the queue
            return ConsumerInterface::MSG_SINGLE_NACK_REQUEUE;
        }

        $this->amqpLogger->info($msg->getBody(), [self::class]);
        if (!$this->validatorService->validateJson($msg->getBody())) {
            return ConsumerInterface::MSG_REJECT;
        }

        try {
            $jobSearchBag      = $this->buildJobSearchBagFromMessage($msg);
            $callableCommand   = $this->buildExecutedCommandName() . implode(" ", $this->getCommandParams($jobSearchBag, $amqpStorageEntity->getId()));
            $isSuccess         = $this->shellCommandService->executeWithLoggedOutput($callableCommand, $this->kernel->getProjectDir());
            $incomingMessageId = $this->amqpService->extractIncomingMessageUniqueId($msg->getBody());
            $searchId          = $this->extractSearchId($msg->getBody());

            // it might happen that something crashed before even the extraction was set
            try {
                $extractionId = $this->getExtractionId($amqpStorageEntity);
            } catch (LogicException) {
                $extractionId = null;
            }

            $extractionStatus = JobOfferExtraction::STATUS_FAILED;
            $percentageDone   = 0;
            if ($isSuccess) {
                $extraction       = $this->entityManager->getRepository(JobOfferExtraction::class)->find($extractionId);
                $percentageDone   = $extraction->getPercentageDone();
                $extractionStatus = $extraction->getStatus();
            }

            if (!$isSuccess && $extractionId) {
                // see the function description to understand why calling this method here
                $this->jobOfferExtractionRepository->updateExtractionStatus($extractionId, JobOfferExtraction::STATUS_FAILED);
            }

            $this->jobSearchDoneProducerService->produce(
                $incomingMessageId,
                $extractionId,
                $searchId,
                $isSuccess,
                $extractionStatus,
                $percentageDone
            );
        } catch (Exception|TypeError $e) {

            /**
             * No matter what is the issue this consumer MUST NOT be re-queued, there are to many services that are
             * going to be called, some will be paid, so having this not controlled being in queue over and over again
             * can be disastrous
             */
            $this->amqpLogger->critical("Got exception in: " . self::class, [
                "exception" => [
                    "class"   => $e::class,
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ],
                "info" => [
                    "what-now" => "Rejecting (no re-queue) the rabbit message",
                    "message"  => $msg->getBody()
                ]
            ]);
            return ConsumerInterface::MSG_REJECT;
        }

        return ConsumerInterface::MSG_ACK;
    }

    /**
     * Return name of the command that is to be executed
     *
     * @return string
     */
    private function buildExecutedCommandName(): string
    {
        $command = "bin/console " . AbstractCommand::PREFIX_NAMESPACE . ":" . AllJobOffersExtractorCommand::COMMAND_NAME . " ";

        return $command;
    }

    /**
     * Will build array of options passed directly to the command
     *
     * @param JobSearchParameterBag $parameterBag
     * @param int                   $amqpStorageId
     *
     * @return array
     */
    public function getCommandParams(JobSearchParameterBag $parameterBag, int $amqpStorageId): array
    {
        $params     = [];
        $parameters = $this->getRawCommandParams($parameterBag, $amqpStorageId);
        foreach ($parameters as $key => $value) {
            $params[] = "--{$key}={$value}";
        }

        return $params;
    }

    /**
     * Will return array of params in their raw not-usable-in-command form
     *
     * @param JobSearchParameterBag $parameterBag
     * @param int                   $amqpStorageId
     *
     * @return array
     */
    private function getRawCommandParams(JobSearchParameterBag $parameterBag, int $amqpStorageId): array
    {
        $optionalParams = [];
        $baseParams     = [
            AbstractJobSearchCommand::OPTION_LONG_MAX_PAGINATION_PAGES_TO_SCRAP => $parameterBag->getPaginationPagesCount(),
            AbstractJobSearchCommand::OPTION_LONG_KEYWORDS                      => "'{$parameterBag->getKeywordsAsString()}'",
            AllJobOffersExtractorCommand::OPTION_COUNTRY                        => $parameterBag->getCountry(),
            AllJobOffersExtractorCommand::OPTION_AMQP_STORAGE_ID                => $amqpStorageId,
        ];

        if (!empty($parameterBag->getLocation())) {
            $optionalParams[AbstractJobSearchCommand::OPTION_LONG_NAME_LOCATION_NAME] = $parameterBag->getLocation();
        }

        if (!empty($parameterBag->getDistance())) {
            $optionalParams[AbstractJobSearchCommand::OPTION_LONG_NAME_DISTANCE] = $parameterBag->getDistance();
        }

        if (!empty($parameterBag->getOffersLimit())) {
            $optionalParams[AbstractJobSearchCommand::OPTION_LONG_NAME_OFFERS_LIMIT] = $parameterBag->getOffersLimit();
        }

        return array_merge($baseParams, $optionalParams);
    }

    /**
     * Will build {@see JobSearchParameterBag} from the content of {@see AMQPMessage},
     * if something goes wrong then null is returned
     *
     * @param AMQPMessage $msg
     *
     * @return JobSearchParameterBag|null
     */
    private function buildJobSearchBagFromMessage(AMQPMessage $msg): ?JobSearchParameterBag
    {
        $dataArray = json_decode($msg->getBody(), true);

        $keywords          = explode(",", $dataArray[DoJobSearchConsumerConstants::KEY_KEYWORDS]);
        $maxPaginationPage = $dataArray[DoJobSearchConsumerConstants::KEY_MAX_PAGINATION_PAGE];
        $locationName      = $dataArray[DoJobSearchConsumerConstants::KEY_LOCATION_NAME];
        $distance          = (int)$dataArray[DoJobSearchConsumerConstants::KEY_DISTANCE];
        $country           = $dataArray[DoJobSearchConsumerConstants::KEY_COUNTRY];
        $offersLimit       = $dataArray[DoJobSearchConsumerConstants::KEY_OFFERS_LIMIT];

        $parameterBag = new JobSearchParameterBag();
        $parameterBag->setKeywords($keywords);
        $parameterBag->setPaginationPagesCount($maxPaginationPage);
        $parameterBag->setLocation($locationName);
        $parameterBag->setDistance($distance);
        $parameterBag->setCountry($country);
        $parameterBag->setOffersLimit($offersLimit);

        return $parameterBag;
    }

    /**
     * Will return id of {@see JobOfferExtraction} bound to the {@see AmqpStorage}
     * throws exception if no binding exists as that's logically incorrect on this step
     *
     * @param AmqpStorage $amqpStorageEntity
     *
     * @return int
     * @throws NonUniqueResultException
     */
    private function getExtractionId(AmqpStorage $amqpStorageEntity): int
    {
        /** @var $extraction2aqmpRepo Extraction2AmqpRequestRepository */
        $extraction2aqmpRepo = $this->entityManager->getRepository(Extraction2AmqpRequest::class);
        $extraction          = $extraction2aqmpRepo->getExtractionForAmqpStorageId($amqpStorageEntity->getId());

        if (empty($extraction)) {
            throw new LogicException("Extraction was not bound to the storage entity of id: {$amqpStorageEntity->getId()}");
        }

        return $extraction->getId();
    }

    /**
     * Will extract the search id from the incoming message
     *
     * @param string $messageBody
     *
     * @return int
     */
    private function extractSearchId(string $messageBody): int
    {
        $dataArray = json_decode($messageBody, true);
        $searchId  = $dataArray[DoJobSearchConsumerConstants::KEY_SEARCH_ID] ?? null;

        if (empty($searchId)) {
            throw new LogicException("Search id is missing in incoming message");
        }

        return $searchId;
    }
}