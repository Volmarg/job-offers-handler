<?php

namespace JobSearcher\Entity\Company;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Entity\Email\Email2Company;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Repository\Company\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WARNING: do not set "unique", constraint. General plan was that companies will be unique,
 *          and that still is the plan, the problem is however that parallel runs are causing to much issues
 *          so on insertion the duplicates WILL be created, bu then over night special command
 *          is executed and it merges & cleans things up.
 *
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 * @ORM\Table(
 *  indexes={
 *      @ORM\Index(name="name", columns={"name"}),
 * })
 *
 * Keep in mind
 * - not saving countryCode & countryName per company, as most offers don't have this data,
 * - country data etc. is being set for {@see Location} which is related to {@see CompanyBranch}
 * - it might happen that few companies got the same country and then one company will get branch of
 *   other company from wrong country but that's a risk to take. It's not that much worth it to spend time on that
 */
class Company
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @Assert\Length(max=500)
     * @Assert\NotNull()
     * @ORM\Column(type="string", length=500)
     */
    private string $name;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $created;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTimeInterface $modified;

    /**
     * @var ArrayCollection<string, JobSearchResult>
     * @ORM\OneToMany(targetEntity=JobSearchResult::class, mappedBy="company", orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $jobOffers;

    /**
     * @var ArrayCollection<string, CompanyBranch>
     * @ORM\OneToMany(targetEntity=CompanyBranch::class, mappedBy="company", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $companyBranches;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $foundedYear = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="array")
     */
    private array $targetIndustries = [];

    /**
     * @Assert\Length(max=50)
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $employeesRange = null;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $linkedinUrl = null;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $twitterUrl = null;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $facebookUrl = null;

    /**
     * @ORM\OneToMany(targetEntity=Email2Company::class, mappedBy="company", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $email2Companies;

    /**
     * @var DateTimeInterface|null $lastDataSearchRun
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $lastDataSearchRun = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $website = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $lastTimeRelatedToOffer = null;

    public function __construct()
    {
        $this->created         = new DateTime();
        $this->modified        = new DateTime();
        $this->jobOffers       = new ArrayCollection();
        $this->companyBranches = new ArrayCollection();
        $this->email2Companies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getModified(): ?DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(?DateTimeInterface $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * @return Collection|JobSearchResult[]
     */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function addJobOffer(JobSearchResult $jobOffer): self
    {
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers[] = $jobOffer;
            $jobOffer->setCompany($this);
        }

        return $this;
    }

    /**
     * @param JobSearchResult[] $jobOffers
     */
    public function addJobOffers(array $jobOffers): void
    {
        foreach ($jobOffers as $jobOffer) {
            $this->addJobOffer($jobOffer);
        }
    }

    public function removeJobOffer(JobSearchResult $jobOffer): self
    {
        if ($this->jobOffers->removeElement($jobOffer)) {
            // set the owning side to null (unless already changed)
            if ($jobOffer->getCompany() === $this) {
                $jobOffer->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * Will return related branch for location name or null if none was found
     *
     * @param string $locationName
     * @return CompanyBranch|null
     */
    public function getBranchForLocation(string $locationName): ?CompanyBranch
    {
        foreach($this->companyBranches as $branch){
            if($branch->getLocation()->getName() === $locationName){
                return $branch;
            }
        }

        return null;
    }

    /**
     * @return Collection|CompanyBranch[]
     */
    public function getCompanyBranches(): Collection
    {
        return $this->companyBranches;
    }

    public function addCompanyBranch(CompanyBranch $companyBranch): self
    {
        if (!$this->companyBranches->contains($companyBranch)) {
            $this->companyBranches[] = $companyBranch;
            $companyBranch->setCompany($this);
        }

        return $this;
    }

    /**
     * @param array $branches
     */
    public function addBranches(array $branches): void
    {
        foreach ($branches as $branch) {
            $this->addCompanyBranch($branch);
        }
    }

    public function removeCompanyBranch(CompanyBranch $companyBranch): self
    {
        if ($this->companyBranches->removeElement($companyBranch)) {
            // set the owning side to null (unless already changed)
            if ($companyBranch->getCompany() === $this) {
                $companyBranch->setCompany(null);
            }
        }

        return $this;
    }

    public function getFoundedYear(): ?int
    {
        return $this->foundedYear;
    }

    public function setFoundedYear(?int $foundedYear): self
    {
        $this->foundedYear = $foundedYear;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTargetIndustries(): ?array
    {
        return $this->targetIndustries;
    }

    public function setTargetIndustries(array $targetIndustries): self
    {
        $this->targetIndustries = $targetIndustries;

        return $this;
    }

    public function getEmployeesRange(): ?string
    {
        return $this->employeesRange;
    }

    public function setEmployeesRange(?string $employeesRange): self
    {
        $this->employeesRange = $employeesRange;

        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): self
    {
        $this->linkedinUrl = $linkedinUrl;

        return $this;
    }

    public function getTwitterUrl(): ?string
    {
        return $this->twitterUrl;
    }

    public function setTwitterUrl(?string $twitterUrl): self
    {
        $this->twitterUrl = $twitterUrl;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(?string $facebookUrl): self
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    /**
     * Check if company has already E-Mail address
     *
     * @param string $emailAddress
     *
     * @return bool
     */
    public function hasEmailAddress(string $emailAddress): bool
    {
        foreach ($this->getEmails() as $email) {
            if ($email->getAddress() === $emailAddress) {
                return true;
            }
        }

        return false;
    }

    /**
     * Will add E-Mail address to the company via the {@see Email2Company}
     *
     * @param Email $email
     */
    public function addEmailAddress(Email $email): void
    {
        if ($this->hasEmailAddress($email->getAddress())) {
            return;
        }

        /**
         * There was some class here used earlier to compare whole objects, but it was consuming A LOT
         * of memory, so it was replaced by these calls. Do NOT try to replace it with any kind of
         * object to object compare logic.
         */
        $isSame = (trim($email->getEmail2Company()->getCompany()->getName()) === trim($this->getName()));

        if (
                !is_null($email->getEmail2Company()->getCompany()->getId())
            &&  !is_null($this->getId())
            &&  $email->getEmail2Company()->getCompany()->getId() !== $this->getId()
        ) {
            $isSame = false;
        }

        if (
                !empty($email->getEmail2Company()->getCompany()->getWebsite())
            &&  $email->getEmail2Company()->getCompany()->getWebsite() !== $this->getWebsite()
        ) {
            $isSame = false;
        }

        // E-Mail can belong to 1 company only, so skipping
        if (
                !empty($email->getEmail2Company())
            &&  !$isSame
        ) {

            return;
        }elseif( // THIS Company is bound to Email, so now also bind the Email to THIS Company in order to persist it
                !empty($email->getEmail2Company())
            &&  $isSame
        ){

            $this->addEmail2Company($email->getEmail2Company());
            return;
        }

        $email2Company = new Email2Company($this, $email);
        $this->addEmail2Company($email2Company);
    }

    /**
     * Will return all emails related to company
     *
     * @return Email[]
     */
    public function getEmails(): array
    {
        $emails = [];

        /** @var $email2Company Email2Company */
        foreach($this->getEmail2Companies()->getValues() as $email2Company){
            $emails[] = $email2Company->getEmail();
        }

        return $emails;
    }

    /**
     * Count emails that are suitable for job applications
     *
     * @return int
     */
    public function countJobApplicationEmails(): int
    {
        return count($this->getJobApplicationEmails());
    }

    /**
     * @return Collection|Email2Company[]
     */
    public function getEmail2Companies(): Collection
    {
        return $this->email2Companies;
    }

    public function addEmail2Company(Email2Company $email2Company): self
    {
        if (!$this->email2Companies->contains($email2Company)) {
            $this->email2Companies[] = $email2Company;
            $email2Company->setCompany($this);
        }

        return $this;
    }

    /**
     * @param ArrayCollection $email2Companies
     */
    public function setEmail2Companies(ArrayCollection $email2Companies): void
    {
        $this->email2Companies = $email2Companies;
    }

    /**
     * @param array $emails2Company
     */
    public function addEmails2Company(array $emails2Company): void
    {
        foreach ($emails2Company as $email) {
            $this->addEmail2Company($email);
        }
    }

    public function removeEmail2Company(Email2Company $email2Company): self
    {
        if ($this->email2Companies->removeElement($email2Company)) {
            // set the owning side to null (unless already changed)
            if ($email2Company->getCompany() === $this) {
                $email2Company->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * Will return array of {@see Email}(s) that can be used for job applications
     *
     * @return Email[]
     */
    public function getJobApplicationEmails(): array
    {
        $emails = [];
        foreach ($this->getEmail2Companies() as $email2Company) {
            if ($email2Company->isForJobApplication()) {
                $emails[] = $email2Company->getEmail();
            }
        }

        return $emails;
    }

    /**
     * Will return first job application E-Mail or null if none is found
     *
     * @return Email|null
     */
    public function getFirstJobApplicationEmail(): ?Email
    {
        if( empty($this->getJobApplicationEmails()) ){
            return null;
        }

        $jobEmails  = $this->getJobApplicationEmails();
        $firstEmail = $jobEmails[array_key_first($jobEmails)];

        return $firstEmail;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getLastDataSearchRun(): ?DateTimeInterface
    {
        return $this->lastDataSearchRun;
    }

    /**
     * @param DateTimeInterface|null $lastDataSearchRun
     */
    public function setLastDataSearchRun(?DateTimeInterface $lastDataSearchRun): void
    {
        $this->lastDataSearchRun = $lastDataSearchRun;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastTimeRelatedToOffer(): ?DateTime
    {
        return $this->lastTimeRelatedToOffer;
    }

    /**
     * @param DateTime|null $lastTimeRelatedToOffer
     */
    public function setLastTimeRelatedToOffer(?DateTime $lastTimeRelatedToOffer): void
    {
        $this->lastTimeRelatedToOffer = $lastTimeRelatedToOffer;
    }

    /**
     * @return void
     */
    public function setLastTimeRelatedToOfferAsToday(): void
    {
        $this->lastTimeRelatedToOffer = new DateTime();
    }

    /**
     * Will return the most often appearing job offers language code
     * @return string|null
     */
    public function getOffersDominatingLanguageIsoCode(): ?string
    {
        if ($this->jobOffers->isEmpty()) {
            return null;
        }

        $countryCodesCount = [];
        foreach ($this->getJobOffers() as $offer) {
            if (!array_key_exists($offer->getOfferLanguageIsoCodeThreeDigit(), $countryCodesCount)) {
                $countryCodesCount[$offer->getOfferLanguageIsoCodeThreeDigit()] = 0;
                continue;
            }

            $countryCodesCount[$offer->getOfferLanguageIsoCodeThreeDigit()]++;
        }

        // it's just null - no language was detected for the offers of this company
        if(
                count($countryCodesCount) === 1
            &&  array_key_exists(null, $countryCodesCount)
        ){
            return null;
        }

        /**
         * Any ISO-code is better than null
         * And this can happen if less offers got language code detected
         */
        $highestCountIsoCode = null;
        $highestCountFound   = 0;
        foreach ($countryCodesCount as $isoCode => $count) {
            if (is_null($isoCode)) {
                continue;
            }

            if ($highestCountFound < $count) {
                $highestCountFound   = $count;
                $highestCountIsoCode = $isoCode;
            }
        }

        return $highestCountIsoCode;
    }

    /**
     * @return string
     */
    public function getAsMd5(): string
    {
        return md5(trim(mb_strtolower($this->getName())) . (mb_strtolower($this->getWebsite() ?? "")));
    }

    /**
     * @return bool
     */
    public function canRemove(): bool
    {
        foreach ($this->companyBranches as $branch) {
            if (!$branch->getJobSearchResults()->isEmpty()) {
                return false;
            }
        }

        return true;
    }
}
