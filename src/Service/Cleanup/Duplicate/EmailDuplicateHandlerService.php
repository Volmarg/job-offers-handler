<?php

namespace JobSearcher\Service\Cleanup\Duplicate;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Email\EmailRepository;
use TypeError;

class EmailDuplicateHandlerService implements DuplicateCleanupInterface
{
    /**
     * Duplicates found within the set that is going to be used for cleanup.
     * So example: {@see EmailRepository::findAllCreatedInDaysOffset()}
     * - returns emails from 24h, toward which clean is going to be handled
     * - in these 24h there might already exist duplicated emails,
     *   such emails then are stored in here
     *
     * @var Email[] $innerDuplicates
     */
    private array $innerDuplicates = [];

    /**
     * @var Email[] $removedDuplicates
     */
    private static array $removedDuplicates = [];

    /**
     * @var int $countOfCleared
     */
    private int $countOfCleared = 0;

    /**
     * @return array
     */
    public static function getRemovedDuplicates(): array
    {
        return self::$removedDuplicates;
    }

    /**
     * @param EmailRepository        $emailRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly EmailRepository        $emailRepository,
        private readonly EntityManagerInterface $entityManager
    ){

    }

    /**
     * {@inheritDoc}
     */
    public function clean(int $maxDaysOffset, array $extractionIds = []): int
    {
        $this->entityManager->beginTransaction();
        try {
            $recentEmails = $this->emailRepository->findAllCreatedInDaysOffset($maxDaysOffset);
            $this->cleanEntities($recentEmails);
            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $this->countOfCleared;
    }

    /**
     * @param array $entities
     */
    public function cleanEntities(array $entities): void
    {
        $filteredEmails = $this->filterInnerDuplicates($entities);
        $this->mergeInnerDuplicates($filteredEmails);
    }

    /**
     * @param Email[] $emails
     *
     * @return Email[]
     */
    private function filterInnerDuplicates(array $emails): array
    {
        $filteredEmails = [];
        $duplicatedEmailIds = [];

        foreach ($emails as $email) {
            if (in_array($email->getId(), $duplicatedEmailIds)) {
                $filteredEmails[] = $email;
                continue;
            }

            foreach ($emails as $duplicatedEmail) {

                if (
                        $email->getId()   !== $duplicatedEmail->getId()
                    &&  $email->getAddress() === $duplicatedEmail->getAddress()
                ) {
                    $duplicatedEmailIds[]  = $duplicatedEmail->getId();
                    $this->innerDuplicates[] = $email;
                    continue 2;
                }

            }

            $filteredEmails[] = $email;
        }

        return $filteredEmails;
    }

    /**
     * @param Email[] $emails
     */
    private function mergeInnerDuplicates(array $emails): void
    {
        foreach ($this->innerDuplicates as $innerDuplicate) {
            foreach ($emails as $email) {

                if ($innerDuplicate->getAddress() === $email->getAddress()) {
                    $this->mergeData($email, $innerDuplicate);
                    $this->countOfCleared++;
                    continue 2;
                }

            }
        }
    }

    /**
     * Merge data from one email to another
     *
     * @param Email $mergedInto
     * @param Email $mergedFrom
     */
    private function mergeData(Email $mergedInto, Email $mergedFrom): void
    {
        /** @var JobSearchResult $offer */
        foreach ($mergedFrom->getJobOffers()->getValues() as $offer) {
            $offer->setEmail($mergedInto);
            $this->entityManager->persist($offer);
        }

        $this->entityManager->persist($mergedInto);
        $this->entityManager->flush();

        // it's a must the Email2Company gets removed first!
        self::$removedDuplicates[] = $mergedFrom->getEmail2Company();
        self::$removedDuplicates[] = $mergedFrom;
    }

}