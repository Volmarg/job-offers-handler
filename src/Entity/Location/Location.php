<?php

namespace JobSearcher\Entity\Location;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\LocationRepository;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WARNING: do not set "unique", constraint. General plan was that companies will be unique,
 *          and that still is the plan, the problem is however that parallel runs are causing to much issues
 *          so on insertion the duplicates WILL be created, bu then over night special command
 *          is executed and it merges & cleans things up.
 *
 * @ORM\Table(
 *     indexes={
 *          @ORM\Index(name="name", columns={"name"}),
 *          @ORM\Index(name="country", columns={"country"})
 *      }
 * )
 * @ORM\Entity(repositoryClass=LocationRepository::class)
 */
class Location
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTime $created;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTime $modified = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $latitude = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $longitude = null;

    /**
     * @ORM\Column(type="string", length=75, nullable=true)
     */
    private $region = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $regionCode = null;

    /**
     * @Assert\Length(max=500)
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $name = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $nativeLanguageCityName = null;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country = null;

    /**
     * @ORM\Column(type="string", length=75, nullable=true)
     */
    private $countryCode = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $continent = null;

    /**
     * @ORM\ManyToMany(targetEntity=JobSearchResult::class, mappedBy="locations")
     * @ORM\JoinTable(name="job_search_result_location")
     *
     */
    private $jobSearchResults;

    /**
     * @ORM\OneToMany(targetEntity=CompanyBranch::class, mappedBy="location")
     */
    private $companyBranches;

    public function __construct(?string $name = null)
    {
        $this->name             = $name;
        $this->created          = new DateTime();
        $this->jobSearchResults = new ArrayCollection();
        $this->companyBranches  = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getRegionCode(): ?string
    {
        return $this->regionCode;
    }

    public function setRegionCode(?string $regionCode): self
    {
        $this->regionCode = $regionCode;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getContinent(): ?string
    {
        return $this->continent;
    }

    public function setContinent(string $continent): self
    {
        $this->continent = $continent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNativeLanguageCityName(): ?string
    {
        return $this->nativeLanguageCityName;
    }

    /**
     * @param string|null $nativeLanguageCityName
     */
    public function setNativeLanguageCityName(?string $nativeLanguageCityName): void
    {
        $this->nativeLanguageCityName = $nativeLanguageCityName;
    }

    /**
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime|null $created
     */
    public function setCreated(?DateTime $created): void
    {
        $this->created = $created;
    }

    /**
     * @return DateTime|null
     */
    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    /**
     * @param DateTime|null $modified
     */
    public function setModified(?DateTime $modified): void
    {
        $this->modified = $modified;
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
            $jobSearchResult->addLocation($this);
        }

        return $this;
    }

    public function removeJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        if ($this->jobSearchResults->removeElement($jobSearchResult)) {
            $jobSearchResult->removeLocation($this);
        }

        return $this;
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
            $companyBranch->setLocation($this);
        }

        return $this;
    }

    public function removeCompanyBranch(CompanyBranch $companyBranch): self
    {
        if ($this->companyBranches->removeElement($companyBranch)) {
            // set the owning side to null (unless already changed)
            if ($companyBranch->getLocation() === $this) {
                $companyBranch->setLocation(null);
            }
        }

        return $this;
    }

    /**
     * Will check if all the most important base properties are filled
     *
     * @return bool
     */
    public function hasAllBaseInformation(): bool
    {
        return (
                !empty($this->getName())
            &&  !empty($this->getCountry())
            &&  !empty($this->getCountryCode())
            &&  !empty($this->getLongitude())
            &&  !empty($this->getLatitude())
        );
    }

    /**
     * @return string
     */
    public function getAsMd5(): string
    {
        return md5(
                trim(mb_strtolower($this->getName()))
            .   trim(mb_strtolower($this->getCountry() ?? ""))
            .   trim(mb_strtolower($this->getRegion() ?? ""))
        );
    }

}
