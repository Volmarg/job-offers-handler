<?php

namespace JobSearcher\Response\Extraction;

use JobSearcher\Action\API\Extraction\ExtractionController;
use JobSearcher\Response\BaseApiResponse;

/**
 * Related to the {@see ExtractionController::$jobOfferExtractionRepository}
 */
class CountRunningExtractionsResponse extends BaseApiResponse
{
    /**
     * @var int $count
     */
    private int $count = 0;

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
    }

}