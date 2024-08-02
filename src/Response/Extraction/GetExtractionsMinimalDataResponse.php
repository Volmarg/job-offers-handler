<?php

namespace JobSearcher\Response\Extraction;

use JobSearcher\Action\API\Extraction\ExtractionController;
use JobSearcher\Response\BaseApiResponse;

/**
 * Related to the {@see ExtractionController::getMinimalData()}
 */
class GetExtractionsMinimalDataResponse extends BaseApiResponse
{
    private array $foundOffersCount = [];
    private array $clientIdStatuses = [];
    private array $clientIdPercentageDone = [];
    private array $clientIdExtractionId = [];

    public function getFoundOffersCount(): array
    {
        return $this->foundOffersCount;
    }

    public function setFoundOffersCount(array $foundOffersCount): void
    {
        $this->foundOffersCount = $foundOffersCount;
    }

    public function addFoundOffersCount(array $data): void
    {
        $this->foundOffersCount = array_replace($this->foundOffersCount, $data);
    }

    public function getClientIdStatuses(): array
    {
        return $this->clientIdStatuses;
    }

    public function setClientIdStatuses(array $clientIdStatuses): void
    {
        $this->clientIdStatuses = $clientIdStatuses;
    }

    public function addClientIdStatuses(array $data): void
    {
        $this->clientIdStatuses = array_replace($this->clientIdStatuses, $data);
    }

    public function getClientIdPercentageDone(): array
    {
        return $this->clientIdPercentageDone;
    }

    public function addClientIdPercentageDone(array $data): void
    {
        $this->clientIdPercentageDone = array_replace($this->clientIdPercentageDone, $data);
    }

    public function setClientIdPercentageDone(array $clientIdPercentageDone): void
    {
        $this->clientIdPercentageDone = $clientIdPercentageDone;
    }

    public function getClientIdExtractionId(): array
    {
        return $this->clientIdExtractionId;
    }

    public function setClientIdExtractionId(array $clientIdExtractionId): void
    {
        $this->clientIdExtractionId = $clientIdExtractionId;
    }

    public function addClientIdExtractionId(array $data): void
    {
        $this->clientIdExtractionId = array_replace($this->clientIdExtractionId, $data);
    }
}