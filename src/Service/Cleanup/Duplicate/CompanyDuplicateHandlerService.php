<?php

namespace JobSearcher\Service\Cleanup\Duplicate;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Company\CompanyRepository;
use TypeError;

class CompanyDuplicateHandlerService implements DuplicateCleanupInterface
{
    /**
     * Duplicates found within the set that is going to be used for cleanup.
     * So example: {@see CompanyRepository::findAllCreatedInDaysOffset()}
     * - returns companies from 24h, toward which clean is going to be handled
     * - in these 24h there might already exist duplicated companies,
     *   such companies then are stored in here
     *
     * @var Company[] $innerDuplicates
     */
    private array $innerDuplicates = [];

    /**
     * @var Company[] $removedDuplicates
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
     * @param CompanyRepository      $companyRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly CompanyRepository $companyRepository,
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
            $recentCompanies = $this->companyRepository->findAllCreatedInDaysOffset($maxDaysOffset);
            $this->cleanEntities($recentCompanies);
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
        $filteredCompanies = $this->filterInnerDuplicates($entities);
        $this->mergeInnerDuplicates($filteredCompanies);
    }

    /**
     * @param Company[] $companies
     *
     * @return Company[]
     */
    private function filterInnerDuplicates(array $companies): array
    {
        $filteredCompanies = [];
        $duplicatedCompanyIds = [];

        foreach ($companies as $company) {
            if (in_array($company->getId(), $duplicatedCompanyIds)) {
                $filteredCompanies[] = $company;
                continue;
            }

            foreach ($companies as $duplicateComparedCompany) {

                if (
                        $company->getId()   !== $duplicateComparedCompany->getId()
                    &&  $company->getAsMd5() === $duplicateComparedCompany->getAsMd5()
                ) {
                    $duplicatedCompanyIds[]  = $duplicateComparedCompany->getId();
                    $this->innerDuplicates[] = $company;
                    continue 2;
                }

            }

            $filteredCompanies[] = $company;
        }

        return $filteredCompanies;
    }

    /**
     * @param Company[] $companies
     */
    private function mergeInnerDuplicates(array $companies): void
    {
        foreach ($this->innerDuplicates as $innerDuplicate) {
            foreach ($companies as $company) {

                if ($innerDuplicate->getAsMd5() === $company->getAsMd5()) {
                    $this->mergeData($company, $innerDuplicate);
                    $this->countOfCleared++;
                    continue 2;
                }

            }
        }
    }

    /**
     * Merge data from one company to another
     *
     * @param Company $mergedInto
     * @param Company $mergedFrom
     */
    private function mergeData(Company $mergedInto, Company $mergedFrom): void
    {
        $this->mergeRelationalData($mergedInto, $mergedFrom);
        $this->mergeUrlData($mergedInto, $mergedFrom);
        $this->mergeDateData($mergedInto, $mergedFrom);
        $this->mergeEmails($mergedInto, $mergedFrom);

        if (!$mergedInto->getDescription() && $mergedFrom->getDescription()) {
            $mergedInto->setDescription($mergedFrom->getDescription());
        }

        if (!empty($mergedInto->getTargetIndustries()) && !empty($mergedFrom->getTargetIndustries())) {
            $mergedInto->setTargetIndustries($mergedFrom->getTargetIndustries());
        }

        if (!$mergedInto->getEmployeesRange() && $mergedFrom->getEmployeesRange()) {
            $mergedInto->setEmployeesRange($mergedFrom->getEmployeesRange());
        }

        if (!$mergedInto->getFoundedYear() && $mergedFrom->getFoundedYear()) {
            $mergedInto->setFoundedYear($mergedFrom->getFoundedYear());
        }

        $this->entityManager->persist($mergedInto);
        $this->entityManager->flush();

        self::$removedDuplicates[] = $mergedFrom;
    }

    /**
     * @param Company $mergedInto
     * @param Company $mergedFrom
     */
    private function mergeUrlData(Company $mergedInto, Company $mergedFrom): void
    {

        if (!$mergedInto->getLinkedinUrl() && $mergedFrom->getLinkedinUrl()) {
            $mergedInto->setLinkedinUrl($mergedFrom->getLinkedinUrl());
        }

        if (!$mergedInto->getTwitterUrl() && $mergedFrom->getTwitterUrl()) {
            $mergedInto->setTwitterUrl($mergedFrom->getTwitterUrl());
        }

        if (!$mergedInto->getWebsite() && $mergedFrom->getWebsite()) {
            $mergedInto->setWebsite($mergedFrom->getWebsite());
        }

        if (!$mergedInto->getFacebookUrl() && $mergedFrom->getFacebookUrl()) {
            $mergedInto->setFacebookUrl($mergedFrom->getFacebookUrl());
        }
    }

    /**
     * @param Company $mergedInto
     * @param Company $mergedFrom
     */
    private function mergeRelationalData(Company $mergedInto, Company $mergedFrom): void
    {
        /** @var JobSearchResult $jobOffer */
        foreach ($mergedFrom->getJobOffers()->getValues() as $jobOffer) {
            $jobOffer->setCompany($mergedInto);
            $this->entityManager->persist($jobOffer);
        }

        /** @var CompanyBranch $branch */
        foreach ($mergedFrom->getCompanyBranches()->getValues() as $branch) {
            $branch->setCompany($mergedInto);
            $this->entityManager->persist($branch);
        }
    }

    /**
     * @param Company $mergedInto
     * @param Company $mergedFrom
     */
    private function mergeDateData(Company $mergedInto, Company $mergedFrom): void
    {
        if (!$mergedInto->getLastDataSearchRun()?->getTimestamp() < $mergedFrom->getLastDataSearchRun()?->getTimestamp()) {
            $mergedInto->setLastDataSearchRun($mergedFrom->getLastDataSearchRun());
        }

        if (!$mergedInto->getLastTimeRelatedToOffer()?->getTimestamp() < $mergedFrom->getLastTimeRelatedToOffer()?->getTimestamp()) {
            $mergedInto->setLastTimeRelatedToOffer($mergedFrom->getLastTimeRelatedToOffer());
        }
    }

    /**
     * @param Company $mergedInto
     * @param Company $mergedFrom
     */
    private function mergeEmails(Company $mergedInto, Company $mergedFrom): void
    {
        foreach ($mergedFrom->getEmail2Companies() as $email2CompanyFrom) {
            if (
                    !$email2CompanyFrom->getEmail()?->getAddress()
                ||  $mergedInto->hasEmailAddress($email2CompanyFrom->getEmail()->getAddress())
            ) {
                continue;
            }

            $mergedFrom->removeEmail2Company($email2CompanyFrom);
            $mergedInto->addEmail2Company($email2CompanyFrom);
            $email2CompanyFrom->setCompany($mergedInto);

            $this->entityManager->persist($email2CompanyFrom);
            $this->entityManager->persist($mergedFrom);
        }
    }

}
