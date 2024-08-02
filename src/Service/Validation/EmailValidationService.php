<?php

namespace JobSearcher\Service\Validation;

use Psr\Log\LoggerInterface;
use SmtpEmailValidatorBundle\Service\SmtpValidator;

/**
 * Provides logic for Email validation
 * It's basically wrapper for {@see SmtpValidator}, but more like THIS project oriented way
 */
class EmailValidationService
{
    public function __construct(
        private readonly SmtpValidator   $smtpValidator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Will validate E-Mail and return bool as indication if E-Mail is valid
     *
     * @param string $emailAddress
     *
     * @return bool
     */
    public function validate(string $emailAddress): bool
    {
        $validationResult = $this->smtpValidator->validateEmail([$emailAddress]);
        if (!($validationResult[$emailAddress] ?? false)) {
            $this->logger->warning("Invalid E-Mail - won't be added (nor saved): {$emailAddress}");

            return false;
        }

        return true;
    }
}