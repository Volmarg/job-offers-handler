<?php

namespace JobSearcher\Entity\Company;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Repository\Company\CompanyBranchRepository;

/**
 * @ORM\Entity(repositoryClass=CompanyBranchRepository::class)
 */
class CompanyBranch
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $phoneNumbers = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTimeInterface $created;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTimeInterface $modified;

    /**
     * @ORM\ManyToOne(targetEntity=Location::class, inversedBy="companyBranches", cascade={"persist"})
     */
    private $location;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="companyBranches")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\OneToMany(targetEntity=JobSearchResult::class, mappedBy="companyBranch")
     */
    private $jobSearchResults;

    public function __construct()
    {
        $this->created  = new DateTime();
        $this->modified = new DateTime();
        $this->jobSearchResults = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhoneNumbers(): ?array
    {
        return $this->phoneNumbers;
    }

    public function setPhoneNumbers(?array $phoneNumbers): self
    {
        $this->phoneNumbers = $phoneNumbers;

        return $this;
    }

    /**
     * Will return first phone number or null if none is found
     *
     * @return string|null
     */
    public function getFirstPhoneNumber(): ?string
    {
        if( empty($this->phoneNumbers) ){
            return null;
        }

        $firstPhoneNumber = $this->phoneNumbers[array_key_first($this->phoneNumbers)];

        return $firstPhoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function addUniquePhoneNumber(string $phoneNumber): void
    {
        if( is_null($this->phoneNumbers) ){
            $this->phoneNumbers = [$phoneNumber];
            return;
        }

        if( !in_array($phoneNumber, $this->phoneNumbers) ){
            $this->phoneNumbers[] = $phoneNumber;
        }
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

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

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

    /**
     * @return Collection|JobSearchResult[]
     */
    public function getJobSearchResults(): Collection
    {
        return $this->jobSearchResults;
    }

    public function addJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        if (!$this->jobSearchResults->contains($jobSearchResult)) {
            $this->jobSearchResults[] = $jobSearchResult;
            $jobSearchResult->setCompanyBranch($this);
        }

        return $this;
    }

    public function removeJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        if ($this->jobSearchResults->removeElement($jobSearchResult)) {
            // set the owning side to null (unless already changed)
            if ($jobSearchResult->getCompanyBranch() === $this) {
                $jobSearchResult->setCompanyBranch(null);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAsMd5(): string
    {
        return md5(trim(mb_strtolower($this->getCompany()->getName())) . (mb_strtolower($this->getLocation()?->getAsMd5() ?? '')));
    }
}
