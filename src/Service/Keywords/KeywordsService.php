<?php

namespace JobSearcher\Service\Keywords;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Exception\Extraction\TerminateProcessException;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use KeywordsFinder\Controller\KeywordsController;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Handles providing keywords for strings
 */
class KeywordsService
{
    public function __construct(
        private KeywordsController                 $keywordsController,
        private LoggerInterface                    $logger,
        private EntityManagerInterface             $entityManager,
        private readonly JobSearchResultRepository $jobSearchResultRepository
    ){}

    /**
     * Will fetch the keywords for {@see JobSearchResult} which don't have any yet,
     * afterwards saves them in database on the offer entity
     *
     * @throws TerminateProcessException
     */
    public function getForOffersWithoutKeywords(): void
    {
        $jobSearchResults = $this->jobSearchResultRepository->getAllWithoutKeywords();
        foreach ($jobSearchResults as $jobSearchResult) {
            $this->getForOffer($jobSearchResult);
        }
    }

    /**
     * Will provide keywords for single {@see JobSearchResult} - if it has keywords already set then nothing is done
     *
     * @param JobSearchResult $jobSearchResult
     *
     * @return void
     * @throws TerminateProcessException
     */
    public function getForOffer(JobSearchResult $jobSearchResult): void
    {
        if (!empty($jobSearchResult->getKeywords())) {
            return;
        }

        try{
            $keywords = $this->keywordsController->get($jobSearchResult->getJobTitle());
            $jobSearchResult->setKeywords($keywords);
            $this->entityManager->persist($jobSearchResult);
            $this->entityManager->flush();
        } catch (Exception|TypeError $e) {
            $this->logger->critical("Exception was thrown while fetching and setting keywords, skipping", [
                "jobSearchResult" => [
                    "id" => $jobSearchResult->getId(),
                ],
                "class"           => self::class,
                "exception"       => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ],
            ]);

            if (!$this->entityManager->isOpen()) {
                throw new TerminateProcessException($e);
            }
        }

    }
}