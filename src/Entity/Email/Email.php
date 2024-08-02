<?php

namespace JobSearcher\Entity\Email;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Repository\Email\EmailRepository;
use Doctrine\ORM\Mapping as ORM;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;

/**
 * WARNING: do not set "unique", constraint. General plan was that companies will be unique,
 *          and that still is the plan, the problem is however that parallel runs are causing to much issues
 *          so on insertion the duplicates WILL be created, bu then over night special command
 *          is executed and it merges & cleans things up.
 *
 *
 * @ORM\Entity(repositoryClass=EmailRepository::class)
 * @ORM\Table(indexes={
 *  @ORM\Index(name="address", columns={"address"})
 * })
 */
class Email
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255, options={"comment": "The E-Mail address"})
     */
    private string $address;

    /**
     * @ORM\Column(type="boolean",name="is_valid_smtp_check")
     */
    private bool $validBySmtpCheck = true;

    /**
     * @ORM\Column(type="boolean",name="is_accepting_emails", options={"comment": "Does this E-Mail address accepts incoming messages at all"})
     */
    private bool $acceptsEmails = true;

    /**
     * @ORM\OneToOne(targetEntity=Email2Company::class, mappedBy="email", cascade={"persist", "remove"})
     */
    private Email2Company $email2Company;

    /**
     * @var ArrayCollection<int, JobSearchResult>
     * @ORM\OneToMany(targetEntity=JobSearchResult::class, mappedBy="email", cascade={"persist", "remove"})
     */
    private $jobOffers;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $created;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTimeInterface $modified;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $lastValidation = null;

    public function __construct(string $address, ?Company $company = null, bool $isApplicationEmail = false)
    {
        $this->created  = new DateTime();
        $this->modified = new DateTime();

        $this->jobOffers = new ArrayCollection();

        if (!empty($company)) {
            $emailToCompany = new Email2Company($company, $this);
            $emailToCompany->setForJobApplication($isApplicationEmail);
            $this->setEmail2Company($emailToCompany);
        }

        $this->address = $address;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function isValidBySmtpCheck(): ?bool
    {
        return $this->validBySmtpCheck;
    }

    public function setValidBySmtpCheck(bool $validBySmtpCheck): self
    {
        $this->validBySmtpCheck = $validBySmtpCheck;

        return $this;
    }

    public function isAcceptingEmails(): ?bool
    {
        return $this->acceptsEmails;
    }

    public function setAcceptsEmails(bool $acceptsEmails): self
    {
        $this->acceptsEmails = $acceptsEmails;

        return $this;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->email2Company->getCompany();
    }

    /**
     * @param Email2Company|null $email2Company
     *
     * @return $this
     */
    public function setEmail2Company(?Email2Company $email2Company): self
    {
        // unset the owning side of the relation if necessary
        if ($email2Company === null && $this->email2Company !== null) {
            $this->email2Company->setEmail(null);
        }

        // set the owning side of the relation if necessary
        if ($email2Company !== null && $email2Company->getEmail() !== $this) {
            $email2Company->setEmail($this);
        }

        $this->email2Company = $email2Company;

        return $this;
    }

    /**
     * @return Email2Company
     */
    public function getEmail2Company(): Email2Company
    {
        return $this->email2Company;
    }

    /**
     * @return ArrayCollection<int, JobSearchResult>
     */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    /**
     * @return DateTime|DateTimeInterface|null
     */
    public function getCreated(): DateTime|DateTimeInterface|null
    {
        return $this->created;
    }

    /**
     * @param DateTime|DateTimeInterface|null $created
     */
    public function setCreated(DateTime|DateTimeInterface|null $created): void
    {
        $this->created = $created;
    }

    /**
     * @return DateTime|DateTimeInterface|null
     */
    public function getModified(): DateTime|DateTimeInterface|null
    {
        return $this->modified;
    }

    /**
     * @param DateTime|DateTimeInterface|null $modified
     */
    public function setModified(DateTime|DateTimeInterface|null $modified): void
    {
        $this->modified = $modified;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getLastValidation(): ?DateTimeInterface
    {
        return $this->lastValidation;
    }

    /**
     * @param DateTimeInterface|null $lastValidation
     */
    public function setLastValidation(?DateTimeInterface $lastValidation): void
    {
        $this->lastValidation = $lastValidation;
    }

    /**
     * @param JobSearchResult $offer
     *
     * @return bool
     */
    public function isFromJobOffer(JobSearchResult $offer): bool
    {
        foreach ($this->jobOffers->getValues() as $relatedOffer) {
            if ($relatedOffer->getId() === $offer->getId()) {
                return true;
            }
        }

        return false;
    }

}
