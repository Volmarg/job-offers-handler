<?php

namespace JobSearcher\Service\Math\Extraction;

use JobSearcher\DTO\JobService\NewAndExistingOffersDto;

/**
 * Counts offers saved for the extraction
 */
class NewAndExistingOffersCounter
{
    /**
     * Count amount of unique entities bound totally
     *
     * @param NewAndExistingOffersDto[] $dtos
     * @param array $boundOfferIds
     * @return int
     */
    public static function countBound(array $dtos = [], array $boundOfferIds = []): int
    {
        $entityIds = [];
        foreach ($dtos as $newAndExistingOffersDto) {
            $ids = [];
            foreach ($newAndExistingOffersDto->getExistingOfferEntities() as $existingOfferEntity) {
                if (in_array($existingOfferEntity->getId(), $boundOfferIds)) {
                    continue;
                }

                $ids[] = $existingOfferEntity->getId();
            }

            $entityIds = array_merge($ids, $entityIds);
        }

        $uniqueIds = array_unique($entityIds);
        $idsCount  = count($uniqueIds);

        return $idsCount;
    }

}