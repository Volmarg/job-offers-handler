<?php

namespace JobSearcher\DTO\Api\Transport\Offer;

/**
 * Offers data for excluding them being fetched
 */
class ExcludedOfferData
{
    public function __construct(
        private readonly string $title,
        private readonly string $companyName,
        private readonly ?int $id = null,
    ){}

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

}