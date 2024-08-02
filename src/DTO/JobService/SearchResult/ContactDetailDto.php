<?php

namespace JobSearcher\DTO\JobService\SearchResult;

/**
 * Contain company details
 */
class ContactDetailDto
{

    private const KEY_EMAIL        = 'email';
    private const KEY_PHONE_NUMBER = 'phoneNumber';
    private const KEY_IS_EMAIL_FROM_OFFER = "isEmailFromOffer";

    /**
     * @var string|null $email
     */
    private ?string $email = "";

    /**
     * @var bool $emailFromJobOffer
     */
    private bool $emailFromJobOffer = false;

    /**
     * @var string|null $phoneNumber
     */
    private ?string $phoneNumber = "";

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return bool
     */
    public function isEmailFromJobOffer(): bool
    {
        return $this->emailFromJobOffer;
    }

    /**
     * @param bool $emailFromJobOffer
     */
    public function setEmailFromJobOffer(bool $emailFromJobOffer): void
    {
        $this->emailFromJobOffer = $emailFromJobOffer;
    }


    /**
     * Return array representation of the dto
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::KEY_EMAIL        => $this->getEmail(),
            self::KEY_PHONE_NUMBER => $this->getPhoneNumber(),
        ];
    }

}