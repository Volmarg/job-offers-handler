<?php

namespace JobSearcher\DTO\Validation;

use App\Service\Validation\ValidationService;

/**
 * This DTO should be used in any logic performing validation
 */
class ValidationResultDTO
{

    /**
     * @var bool $success
     */
    private bool $success;

    /**
     * @var array $violationsWithMessages
     */
    private array $violationsWithMessages = [];

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * Will return grouped violations with messages
     * - {@see ValidationService::validateAndReturnArrayOfInvalidFieldsWithMessages()}
     *
     * @return array
     */
    public function getViolationsWithMessages(): array
    {
        return $this->violationsWithMessages;
    }

    /**
     * @param array $violationsWithMessages
     */
    public function setViolationsWithMessages(array $violationsWithMessages): void
    {
        $this->violationsWithMessages = $violationsWithMessages;
    }

}