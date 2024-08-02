<?php

namespace JobSearcher\DTO\JobService\SearchConfiguration\Base\SearchUriBase;

/**
 * Provides different base search uris
 */
class BaseSearchUriDto
{
    public function __construct(
        private readonly string $standard,
        private readonly ?string $sortedLatestFirst = null,
    ) {}

    /**
     * @return string
     */
    public function getStandard(): string
    {
        return $this->standard;
    }

    /**
     * @return string|null
     */
    public function getSortedLatestFirst(): ?string
    {
        return $this->sortedLatestFirst;
    }

    /**
     * Will return array of all uris
     *
     * @return array
     */
    public function getAllUris(): array
    {
        $uris = [
            $this->getStandard(),
        ];

        if (!empty($this->getSortedLatestFirst())) {
            $uris[] = $this->getSortedLatestFirst();
        }

        return $uris;
    }

}
