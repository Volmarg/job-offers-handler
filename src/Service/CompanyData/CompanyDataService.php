<?php

namespace JobSearcher\Service\CompanyData;

use CompanyDataProvider\Controller\Provider\CompanyDataProviderController;
use CompanyDataProvider\DTO\CompanyDataDto;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Exception\Extraction\TerminateProcessException;
use JobSearcher\Repository\Company\CompanyRepository;
use JobSearcher\Service\Email\EmailService;
use JobSearcher\Service\Validation\EmailValidationService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TypeError;

/**
 * Handles providing company data
 */
class CompanyDataService
{

    public function __construct(
        protected LoggerInterface               $logger,
        private CompanyDataProviderController   $companyDataProviderController,
        private EntityManagerInterface          $entityManager,
        private SerializerInterface             $serializer,
        private readonly EmailService           $emailService,
        private readonly CompanyRepository      $companyRepository,
        private readonly EmailValidationService $emailValidationService
    ){}

    /**
     * Handles providing information for {@see Company} and {@see CompanyBranch},
     * afterwards saves the data in database in the entity tables
     *
     * @param DateTime $minLastDataSearchRun
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws TerminateProcessException
     */
    public function getCompaniesData(DateTime $minLastDataSearchRun): void
    {
        $companies = $this->companyRepository->findForCompanyDataProvider($minLastDataSearchRun);
        foreach ($companies as $company) {
            foreach ($company->getCompanyBranches() as $branch) {
                $this->getForCompanyBranch($branch);
            }
        }
    }

    /**
     * Will attempt to provide data for {@see CompanyBranch}, such as:
     * - job application email,
     * - website url,
     * - etc.
     *
     * @param CompanyBranch $branch
     * @param string|null   $isoCodeThreeDigit
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws TerminateProcessException
     */
    public function getForCompanyBranch(CompanyBranch $branch, ?string $isoCodeThreeDigit = null): void
    {
        $companyDataDto = $this->prepareForBranch($branch, $isoCodeThreeDigit);
        if (is_bool($companyDataDto) && !$companyDataDto) {
            return;
        }

        $this->handlePreparedData($branch, $companyDataDto);
    }

    /**
     * Sets the most important fields of {@see Company} from data inside {@see CompanyDataDto}
     * Info: no return due to setting object props, original object gets changed
     *
     * @param Company        $company
     * @param CompanyDataDto $companyDataDto
     */
    private function fillCompanyDataFromProvider(Company $company, CompanyDataDto $companyDataDto): void
    {
        $company->setDescription($companyDataDto->getCompanyDescription());
        $company->setFoundedYear($companyDataDto->getFoundedYear());
        $company->setTargetIndustries($companyDataDto->getTargetIndustries());
        $company->setEmployeesRange($companyDataDto->getEmployeesNumber());
        $company->setTwitterUrl($companyDataDto->getTwitterUrl());
        $company->setLinkedinUrl($companyDataDto->getLinkedinUrl());
        $company->setFacebookUrl($companyDataDto->getFacebookUrl());
        $company->setWebsite($companyDataDto->getWebsite());

        /**
         * INFO: the job application E-Mails MUST be inserted first, since the duplicates are getting ignored,
         *       The thing is that `application` E-Mail is also present in `normal` E-Mails, so if normal is inserted first
         *       then `application` one is not getting marked as "can be used for application"
         */
        foreach ($companyDataDto->getJobApplicationEmails() as $emailAddressString) {
            if (!$this->emailValidationService->validate($emailAddressString)) {
                return;
            }

            $email = new Email($emailAddressString, $company);
            $email->getEmail2Company()->setForJobApplication(true);
            $company->addEmailAddress($this->emailService->decideUsedEmail($email));
        }

        foreach ($companyDataDto->getEmails() as $emailAddressString) {
            if (!$this->emailValidationService->validate($emailAddressString)) {
                return;
            }

            $email = new Email($emailAddressString, $company);
            $company->addEmailAddress($this->emailService->decideUsedEmail($email));
        }

    }

    /**
     * Will attempt to obtain all the data for company.
     *
     * Returns:
     * - null if data could not be obtained,
     * - false if something went wrong,
     * - {@see CompanyDataDto} if data was obtained,
     *
     * @param CompanyBranch $branch
     * @param string|null   $isoCodeThreeDigit
     *
     * @return CompanyDataDto|false|null
     *
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws GuzzleException
     */
    private function prepareForBranch(CompanyBranch $branch, ?string $isoCodeThreeDigit = null): CompanyDataDto | false | null
    {
        // this has to be in separated try/catch as there are sometimes issues with saving fetched data in DB
        try {
            $message = "Now getting data for company branch. Id: {$branch->getId()}. Company name {$branch->getCompany()->getName()}.";
            if (!empty($branch->getLocation())) {
                $message .= "Location name: {$branch->getLocation()->getName()}";
            }

            $this->logger->info($message);

            $usedIsoCode    = $isoCodeThreeDigit ?? $branch->getCompany()->getOffersDominatingLanguageIsoCode();
            $companyDataDto = $this->companyDataProviderController->getForCompany(
                $branch->getCompany()->getName(),
                $branch->getLocation()?->getName(),
                $usedIsoCode
            );
        } catch (Exception $e) {
            $this->logger->critical("Exception was thrown while fetching company data, skipping", [
                "class"     => self::class,
                "data"      => [
                    "companyBranchId" => $branch->getId(),
                ],
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                    "class"   => $e::class,
                ]
            ]);
            return false;
        }

        return $companyDataDto;
    }

    /**
     * Will handle the data obtained from provider
     *
     * @param CompanyBranch       $branch
     * @param CompanyDataDto|null $companyDataDto
     *
     * @return void
     * @throws TerminateProcessException
     */
    private function handlePreparedData(CompanyBranch $branch, CompanyDataDto|null $companyDataDto): void
    {
        try {
            $this->entityManager->beginTransaction();
            if (!empty($companyDataDto)) {
                $company = $branch->getCompany();

                $this->fillCompanyDataFromProvider($company, $companyDataDto);
                $this->entityManager->persist($company);
            }

            $branch->getCompany()->setLastDataSearchRun(new DateTime());

            $this->entityManager->persist($branch);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            $this->logger->critical("Exception was thrown while saving company data, skipping", [
                "class"     => self::class,
                "data"      => [
                    "companyDataDtoSerialized" => $this->serializer->serialize($companyDataDto, "json"),
                ],
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                    "class"   => $e::class,
                ]
            ]);

            if (!$this->entityManager->isOpen()) {
                throw new TerminateProcessException($e);
            }
        }

    }

}