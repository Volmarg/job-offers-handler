<?php

namespace JobSearcher\Service\Cleanup\Duplicate;

interface DuplicateCleanupInterface
{
    /**
     * @param int   $maxDaysOffset
     * @param array $extractionIds
     *
     * @return int
     */
    public function clean(int $maxDaysOffset, array $extractionIds = []): int;

    /**
     * @param array $entities
     */
    public function cleanEntities(array $entities): void;

    /**
     * @return array
     */
    public static function getRemovedDuplicates(): array;
}