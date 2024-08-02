<?php

namespace JobSearcher\Service\CompanyData;

use CompanyDataProvider\Service\Decider\CompanyEmailDecider;
use CompanyDataProvider\Service\Provider\Email\EmailProviderService;
use CompanyDataProvider\Service\Provider\Email\LinkEmailExtractor;
use Exception;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Service\Email\EmailService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Handles providing company email
 */
class CompanyEmailService
{

    public function __construct(
        private readonly LoggerInterface      $logger,
        private readonly LinkEmailExtractor   $linkEmailExtractor,
        private readonly EmailService         $emailService,
        private readonly EmailProviderService $emailProviderService
    ){}

    /**
     * Will search for emails on website of provided company
     *
     * @param Company $company
     *
     * @return void
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function searchOnCompanyWebsite(Company $company): void
    {
        try{
            if (empty($company->getWebsite())) {
                $this->logger->error("Provided company has no website: {$company->getId()} - skipping!");
                return;
            }

            $this->logger->info("Now searching for company: {$company->getId()} / {$company->getName()}");

            $emailAddresses = $this->linkEmailExtractor->get(
                    $company->getWebsite(),
                    $company->getName(),
                    false,
                    false
                ) ?? [];

            $jobApplicationEmails = CompanyEmailDecider::filterJobApplicationEmails($emailAddresses);

            $this->emailService->addEmailsToCompany($jobApplicationEmails, $company, true);
            $this->emailService->addEmailsToCompany($emailAddresses, $company);
        } catch (Exception|TypeError $e) {
            $this->logger->warning("Failed - exception in company data provider: {$e->getMessage()}");
        }
    }

    /**
     * Will search for emails of single company, but will go through the whole fetching process on the company website,
     * it's made this way because companies got the `main` website saved in the DB - that's often enough,
     * but it some cases the search engine should be used to find pages that could have emails on them
     *
     * @param Company $company
     *
     * @return void
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function getEmailsForCompany(Company $company): void
    {
        $this->logger->info("Now searching for company: {$company->getId()} / {$company->getName()}");

        if (empty($company->getCompanyBranches())) {
            $this->logger->error("No branch exists for this company! Bug!");
        }

        foreach ($company->getCompanyBranches() as $branch) {
            $this->logger->info("Branch: {$branch->getId()} / {$branch->getLocation()->getName()}");

            try {
                $emailsFromProvider = $this->emailProviderService->getFromWebsite(
                    $company->getName(),
                    $branch->getLocation()->getName(),
                    $company->getOffersDominatingLanguageIsoCode()
                );

                $emailsDto          =  $emailsFromProvider ?? [];
            } catch (Exception|TypeError $e) {
                $this->logger->warning("Failed - exception in company data provider: {$e->getMessage()}");
                continue;
            }

            if (empty($emailsDto)) {
                $this->logger->warning("Failed - no emails were found");
                continue;
            }

            $this->emailService->addEmailsToCompanyFromEmailsDtos($emailsDto, $company);
            break; // that's enough - having emails by one branch is just fine
        }
    }

}