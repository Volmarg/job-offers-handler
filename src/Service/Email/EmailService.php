<?php

namespace JobSearcher\Service\Email;

use CompanyDataProvider\DTO\Provider\Email\CompanyEmailsDto;
use CompanyDataProvider\Service\Provider\Email\EmailProviderService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Entity\Email\Email2Company;
use JobSearcher\Entity\Email\EmailSource;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Service\Validation\EmailValidationService;

/**
 * Provides logic related to the {@see Email} & {@see Email2Company} {@see EmailSource}
 */
class EmailService
{
    /**
     * This is added as the "solution" to issues: "Doctrine unique constraint violation, duplicated email".
     * That issue happens because the emails are first being found, might be that there are 2 offers for the same companies
     * and so 2 the same emails will be persisted.
     *
     * Now the "persisted" state is a problem here because it's impossible to check if the email is already persisted
     * or flushed (because persisted if pre-flush), so this array allows re-using "new emails" thanks to the fact that
     * php and doctrine track the objects via reference so if the same object is being BOUND to multiple offers,
     * then it's still persisted only once.
     *
     * @var array
     */
    private static array $newEmails = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailValidationService $emailValidationService,
    ){}

    /**
     * Will create {@see Email} & {@see Email2Company}
     *
     * @param JobSearchResult $jobSearchResult
     * @param string          $emailAddress
     * @param bool            $isApplicationEmail
     */
    public function buildAndBindEmailToJobOffer(JobSearchResult $jobSearchResult, string $emailAddress, bool $isApplicationEmail = false): void
    {
        if (!$this->emailValidationService->validate($emailAddress)) {
            return;
        }

        $email     = new Email($emailAddress, $jobSearchResult->getCompany(), $isApplicationEmail);
        $usedEmail = $this->decideUsedEmail($email);
        $jobSearchResult->setEmail($usedEmail);
    }

    /**
     * Will search for already existing E-Mail address, if such exists then it will be returned instead of the provided one
     *
     * @param Email $email
     *
     * @return Email
     */
    public function decideUsedEmail(Email $email): Email
    {
        $existingEmail = $this->entityManager->getRepository(Email::class)->findByAddress($email->getAddress());
        if (!empty($existingEmail)) {
            return $existingEmail;
        }

        if (array_key_exists($email->getAddress(), self::$newEmails)) {
            return self::$newEmails[$email->getAddress()];
        }

        self::$newEmails[$email->getAddress()] = $email;

        return $email;
    }

    /**
     * Will return one E-Mail used for job application, or null if none is found
     *
     * @param JobSearchResult $jobSearchResult
     *
     * @return Email|null
     */
    public function getEmailUsedForJobApplication(JobSearchResult $jobSearchResult): ?Email
    {
        // At first take the E-Mail directly from the job offer as there might be one assigned to it in the job offer itself
        if (!empty($jobSearchResult->getEmail())) {
            return $jobSearchResult->getEmail();
        }

        foreach ($jobSearchResult->getCompany()->getJobApplicationEmails() as $jobApplicationEmail) {
            foreach (EmailProviderService::getJobApplicationEmailPreferredSubstrings() as $preferredString) {
                if (str_contains($jobApplicationEmail->getAddress(), $preferredString)) {
                    return $jobApplicationEmail;
                }
            }
        }

        return $jobSearchResult->getCompany()->getFirstJobApplicationEmail();
    }

    /**
     * Will add E-Mails to company and save them in DB,
     * persist + flush must be done one after another because otherwise there are issues with duplicated emails
     * being inserted into DB.
     *
     * It could be solved in backend with some extra logic but skipping it - not worth it,
     * With current solution of saving every email the duplicate id issue is solved by using {@see EmailService::decideUsedEmail()}
     * - this won't work properly with persisting it all first and then saving since DB is not yet aware of the
     *   emails that were just persisted,
     *
     * @param array   $emails
     * @param Company $company
     * @param bool    $isJobApplicationEmail
     *
     * @return void
     */
    public function addEmailsToCompany(array $emails, Company $company, bool $isJobApplicationEmail = false): void
    {
        foreach ($emails as $emailAddress) {
            if (!$this->emailValidationService->validate($emailAddress)) {
                return;
            }

            $email = new Email($emailAddress, $company, $isJobApplicationEmail);
            $email = $this->decideUsedEmail($email);
            $company->addEmailAddress($email);
            $this->entityManager->persist($company);
            $this->entityManager->flush();
        }
    }

    /**
     * Will add emails from {@see CompanyEmailsDto}(s)
     *
     * @param CompanyEmailsDto   $emailsDto
     * @param Company $company
     *
     * @return void
     */
    public function addEmailsToCompanyFromEmailsDtos(CompanyEmailsDto $emailsDto, Company $company): void
    {
        // application emails must be added first, else will be saved with incorrect status
        foreach ($emailsDto->getJobApplicationEmails() as $emailAddress) {
            $this->addEmailsToCompany([$emailAddress], $company, true);
        }

        foreach ($emailsDto->getEmails() as $emailAddress) {
            $this->addEmailsToCompany([$emailAddress], $company);
        }
    }

    /**
     * Will take the emails and set their {@see Email::$lastValidation} to provided date (NOW if omitted)
     *
     * @param Email[]  $emails
     * @param DateTime $dateTime
     *
     * @return void
     */
    public function setValidationDate(array $emails, DateTime $dateTime = new DateTime()): void
    {
        foreach ($emails as $email) {
            $email->setLastValidation($dateTime);
            $this->entityManager->persist($email);
        }

        $this->entityManager->flush();
    }

}