<?php

namespace JobSearcher\Entity\Email;

use DateTime;
use DateTimeInterface;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Repository\Email\Email2CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Email2CompanyRepository::class)
 * @ORM\Table(name="email_2_company", uniqueConstraints={
 *        @ORM\UniqueConstraint(name="company_email",
 *            columns={"email_id", "company_id"})
 * })
 */
class Email2Company
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $created;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTimeInterface $modified;

    /**
     * @ORM\OneToOne(targetEntity=Email::class, inversedBy="email2Company", cascade={"persist", "remove"})
     */
    private ?Email $email;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="email2Companies")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="boolean", name="is_for_job_application")
     */
    private bool $forJobApplication;

    public function __construct(Company $company, Email $email)
    {
        $this->created  = new DateTime();
        $this->modified = new DateTime();

        $this->company = $company;
        $this->email   = $email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(?Email $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function isForJobApplication(): ?bool
    {
        return $this->forJobApplication;
    }

    public function setForJobApplication(bool $forJobApplication): self
    {
        $this->forJobApplication = $forJobApplication;

        return $this;
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
}
