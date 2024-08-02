<?php

namespace JobSearcher\Service\Languages;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use Lingua\Controller\LanguageDetectionController;

/**
 * Provides (human/spoken) language detection functionality
 */
class LanguageDetectionService
{
    private const MAX_PARALLEL_PROCESSES = 80;

    public function __construct(
        private LanguageDetectionController        $languageDetectionController,
        private EntityManagerInterface             $entityManager,
        private readonly JobSearchResultRepository $jobSearchResultRepository
    ){}

    /**
     * Will search for languages for all the {@see JobSearchResult} that don't have any language set, yet
     * it doesn't check for user that is logged in etc., so this code should be more like "executed overnight",
     * all called only in some special cases.
     *
     * After the languages get fetched, these are then saved in db for job offers
     *
     * @throws Exception
     */
    public function getForOffersWithoutLanguages(): void
    {
        $jobSearchResults = $this->jobSearchResultRepository->getAllWithoutLanguages();
        $this->getForOffers($jobSearchResults);

        $this->entityManager->flush();
    }

    /**
     * Will return language information for job offers
     * - will update the entity, persist it but without flushing
     *
     * Info: need to send job offers in chunk as the language detection is happening parallel
     *       and that's high hardware consumption happening there. If to many processes are running
     *       on the same time then Linux will just crash.
     *
     * @param JobSearchResult[] $jobOffers
     *
     * @return void
     * @throws Exception
     */
    public function getForOffers(array $jobOffers): void
    {
        /** @var Array<Array<JobSearchResult>> $offersChunks*/
        $offersChunks = array_chunk($jobOffers, self::MAX_PARALLEL_PROCESSES);
        foreach($offersChunks as $offersChunk){

            $offersDescriptions = [];
            foreach ($offersChunk as $jobOffer) {
                $preNormalizedDescription = trim(strip_tags($jobOffer->getJobDescription()));
                if (!empty($preNormalizedDescription)) {
                    $offersDescriptions[] = $jobOffer->getJobDescription();
                }
            }

            $languagesInformation = $this->languageDetectionController->getLanguagesInformation($offersDescriptions, "en");
            foreach ($offersChunk as $jobOffer) {

                $descriptionMd5 = md5($jobOffer->getJobDescription());
                foreach ($languagesInformation as $languageInformationDto) {
                    if (md5($languageInformationDto->getText()) !== $descriptionMd5) {
                        continue;
                    }

                    $jobOffer->setOfferLanguage($languageInformationDto->getLanguageName());
                    $jobOffer->setOfferLanguageIsoCodeThreeDigit($languageInformationDto->getThreeDigitLanguageCode());
                    $jobOffer->setMentionedHumanLanguages($languageInformationDto->getMentionedLanguages());
                    $this->entityManager->persist($jobOffer);

                    break;
                }
            }
        }
    }
}