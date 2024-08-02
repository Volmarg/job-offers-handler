<?php

namespace JobSearcher\Entity\JobSearchResult;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use JobSearcher\Constants\JobOfferService\FrenchJobOfferService;
use JobSearcher\Constants\JobOfferService\GermanJobOfferService;
use JobSearcher\Constants\JobOfferService\NorwegianJobOfferService;
use JobSearcher\Constants\JobOfferService\PolishJobOfferService;
use JobSearcher\Constants\JobOfferService\SpanishJobOfferService;
use JobSearcher\Constants\JobOfferService\SwedishJobOfferService;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Company\CompanyBranch;
use JobSearcher\Entity\Email\Email;
use JobSearcher\Entity\Extraction\ExtractionKeyword2Offer;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\France\ApecFrResult;
use JobSearcher\Entity\JobSearchResult\France\CadremploiFrResult;
use JobSearcher\Entity\JobSearchResult\France\HelloWorkComFrResult;
use JobSearcher\Entity\JobSearchResult\France\IndeedFrResult;
use JobSearcher\Entity\JobSearchResult\France\JobiJobaComFrResult;
use JobSearcher\Entity\JobSearchResult\France\OneJeuneOneSolutionGouvFrResult;
use JobSearcher\Entity\JobSearchResult\General\GeneralSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\BankJobDeJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\IndeedDeJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\JobsDeJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\JobwareJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\KimetaDeJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\MonsterDeJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\StepstoneDeJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\TiderDeJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Germany\XingComJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Norway\IndeedNoResult;
use JobSearcher\Entity\JobSearchResult\Norway\JobbSafariNorResult;
use JobSearcher\Entity\JobSearchResult\Norway\JobsInNorwayComNorResult;
use JobSearcher\Entity\JobSearchResult\Norway\JoobleOrgNorJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\AplikujPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\FachPracaPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\GoldenLinePlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\GoWorkPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\IndeedPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\InfopracaPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\InterviewMePlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\JobsPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\JoobleOrgJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\PracaPllJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\PracujPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\Poland\TheProtocolItPlJobSearchResult;
use JobSearcher\Entity\JobSearchResult\France\MonsterFrResult;
use JobSearcher\Entity\JobSearchResult\Spain\ElempleoEspResult;
use JobSearcher\Entity\JobSearchResult\Spain\EsJoobleOrgResult;
use JobSearcher\Entity\JobSearchResult\Spain\IndeedEspResult;
use JobSearcher\Entity\JobSearchResult\Spain\InfoEmpleoComEspResult;
use JobSearcher\Entity\JobSearchResult\Spain\InfoJobsNetResult;
use JobSearcher\Entity\JobSearchResult\Spain\TalentEspResult;
use JobSearcher\Entity\JobSearchResult\Sweden\JobbBlocketSeSweResult;
use JobSearcher\Entity\JobSearchResult\Sweden\JobbGuruSweResult;
use JobSearcher\Entity\JobSearchResult\Sweden\JobbSafariSweResult;
use JobSearcher\Entity\JobSearchResult\Sweden\JoblandSeSweResult;
use JobSearcher\Entity\JobSearchResult\Sweden\JobsInStockholmComSweResult;
use JobSearcher\Entity\JobSearchResult\Sweden\JoobleSweOrgResult;
use JobSearcher\Entity\JobSearchResult\Sweden\MonsterSeSweResult;
use JobSearcher\Entity\Location\Location;
use JobSearcher\Service\TypeProcessor\StringTypeProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All job offer search result tables should extend from this one.
 * Normally it would be more efficient to store job search results into separated tables per portal,
 * that's not possible due to breaking `ManyToMany` relation with such approach.
 *
 * If Other entities extend from this one without discriminator mapping then `ManyToMany` does not know which
 * table it should join to, mapping fixes that issue.
 *
 * Info:
 * {#1 dual relation to @see Location}
 *  - despite the fact that offer is related to {@see Company} -> {@see CompanyBranch} -> {@see Location}
 *  - there is still relation directly to {@see Location} as it might be that job offer has no company data, etc.
 *
 * @Entity
 * @ORM\Table(name="job_search_result")
 * @InheritanceType("JOINED") // must remain as annotation, else doctrine can't read php8.0 attribute, dunno why
 * @DiscriminatorColumn(name="discr", type="string")
 */
#[DiscriminatorMap([
    IndeedDeJobSearchResult::class,
    KimetaDeJobSearchResult::class,
    StepstoneDeJobSearchResult::class,
    XingComJobSearchResult::class,
    GeneralSearchResult::class,
    JobsDeJobSearchResult::class,
    JobwareJobSearchResult::class,
    MonsterDeJobSearchResult::class,
    TiderDeJobSearchResult::class,
    BankJobDeJobSearchResult::class,
    AplikujPlJobSearchResult::class,
    FachPracaPlJobSearchResult::class,
    GoldenLinePlJobSearchResult::class,
    GoWorkPlJobSearchResult::class,
    IndeedPlJobSearchResult::class,
    InfopracaPlJobSearchResult::class,
    InterviewMePlJobSearchResult::class,
    JobsPlJobSearchResult::class,
    JoobleOrgJobSearchResult::class,
    PracaPllJobSearchResult::class,
    PracujPlJobSearchResult::class,
    TheProtocolItPlJobSearchResult::class,
    CadremploiFrResult::class,
    OneJeuneOneSolutionGouvFrResult::class,
    IndeedFrResult::class,
    ApecFrResult::class,
    MonsterFrResult::class,
    ElempleoEspResult::class,
    TalentEspResult::class,
    InfoEmpleoComEspResult::class,
    InfoJobsNetResult::class,
    IndeedEspResult::class,
    EsJoobleOrgResult::class,
    JoobleSweOrgResult::class,
    MonsterSeSweResult::class,
    JoblandSeSweResult::class,
    JobbBlocketSeSweResult::class,
    JobbGuruSweResult::class,
    JobbSafariSweResult::class,
    JobsInStockholmComSweResult::class,
    IndeedNoResult::class,
    JobbSafariNorResult::class,
    JobsInNorwayComNorResult::class,
    JoobleOrgNorJobSearchResult::class,
])]
abstract class JobSearchResult
{
    private const SHORT_DESCRIPTION_MAX_LENGTH = 800;

    /**
     * Mapping between the configuration name and the corresponding entity to it
     * Each of given configuration should have its job offer search results stored in table
     * represented by corresponding entity
     */
    public const MAPPING_JOB_CONFIGURATION_NAME_TO_ENTITY_FQN = [
        PolishJobOfferService::NO_FLUFFY_JOBS_COM => GeneralSearchResult::class,

        PolishJobOfferService::APLIKUJ_PL      => AplikujPlJobSearchResult::class,
        PolishJobOfferService::FACH_PRACA_PL   => FachPracaPlJobSearchResult::class,
        PolishJobOfferService::GOLDENLINE_PL   => GoldenLinePlJobSearchResult::class,
        PolishJobOfferService::INDEED_PL       => IndeedPlJobSearchResult::class,
        PolishJobOfferService::INFOPRACA_PL    => InfopracaPlJobSearchResult::class,
        PolishJobOfferService::JOBS_PL         => JobsPlJobSearchResult::class,
        PolishJobOfferService::JOOBLE_ORG      => JoobleOrgJobSearchResult::class,
        PolishJobOfferService::PRACA_PL        => PracaPllJobSearchResult::class,
        PolishJobOfferService::PRACUJ_PL       => PracujPlJobSearchResult::class,
        PolishJobOfferService::THE_PROTOCOL_IT => TheProtocolItPlJobSearchResult::class,
        PolishJobOfferService::GO_WORK_PL      => GoWorkPlJobSearchResult::class,
        PolishJobOfferService::INTERVIEW_ME_PL => InterviewMePlJobSearchResult::class,

        GermanJobOfferService::INDEED             => IndeedDeJobSearchResult::class,
        GermanJobOfferService::KIMETA             => KimetaDeJobSearchResult::class,
        GermanJobOfferService::STEPSTONE          => StepstoneDeJobSearchResult::class,
        GermanJobOfferService::XING_COM           => XingComJobSearchResult::class,
        GermanJobOfferService::JOBS_DE            => JobsDeJobSearchResult::class,
        GermanJobOfferService::JOBWARE_DE         => JobwareJobSearchResult::class,
        GermanJobOfferService::MONSTER_DE         => MonsterDeJobSearchResult::class,
        GermanJobOfferService::TIDERI_DE          => TiderDeJobSearchResult::class,
        GermanJobOfferService::BANK_JOB_DE        => BankJobDeJobSearchResult::class,

        FrenchJobOfferService::JOBI_JOBA_COM  => JobiJobaComFrResult::class,
        FrenchJobOfferService::INDEED         => IndeedFrResult::class,
        FrenchJobOfferService::HELLO_WORK_COM => HelloWorkComFrResult::class,
        FrenchJobOfferService::CADREMPLOI     => CadremploiFrResult::class,
        FrenchJobOfferService::APEC_FR        => ApecFrResult::class,
        FrenchJobOfferService::MONSTER_FR     => MonsterFrResult::class,
        FrenchJobOfferService::ONE_JEUNE_ONE_SOLUTION => OneJeuneOneSolutionGouvFrResult::class,

        SpanishJobOfferService::ELEMPLEO     => ElempleoEspResult::class,
        SpanishJobOfferService::TALENT_COM   => TalentEspResult::class,
        SpanishJobOfferService::INFO_EMPLEO  => InfoEmpleoComEspResult::class,
        SpanishJobOfferService::INFOJOBS_NET => InfoJobsNetResult::class,
        SpanishJobOfferService::INDEED       => IndeedEspResult::class,
        SpanishJobOfferService::JOOBLE_ORG   => EsJoobleOrgResult::class,

        SwedishJobOfferService::JOOBLE_ORG         => JoobleSweOrgResult::class,
        SwedishJobOfferService::MONSTER            => MonsterSeSweResult::class,
        SwedishJobOfferService::JOBLAND            => JoblandSeSweResult::class,
        SwedishJobOfferService::JOBB_BLOCKET       => JobbBlocketSeSweResult::class,
        SwedishJobOfferService::JOBB_GURU          => JobbGuruSweResult::class,
        SwedishJobOfferService::JOBB_SAFARI        => JobbSafariSweResult::class,
        SwedishJobOfferService::JOBS_IN_STOCKHOLM  => JobsInStockholmComSweResult::class,

        NorwegianJobOfferService::INDEED         => IndeedNoResult::class,
        NorwegianJobOfferService::JOBB_SAFARI    => JobbSafariNorResult::class,
        NorwegianJobOfferService::JOBS_IN_NORWAY => JobsInNorwayComNorResult::class,
        NorwegianJobOfferService::JOOBLE_ORG     => JoobleOrgNorJobSearchResult::class,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $modified;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $jobPostedDateTime = null;

    /**
     * Text is required because some job offers have really some strange, long titles
     * @ORM\Column(type="text")
     */
    private string $jobTitle;

    /**
     * @ORM\Column(type="text")
     */
    private string $jobDescription;

    /**
     * @ORM\Column(type="text")
     */
    private string $jobOfferUrl;

    /**
     * @Assert\Length(max=255)
     * @Assert\NotNull()
     * @ORM\Column(type="string", length=255)
     */
    private string $jobOfferHost;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $mentionedHumanLanguages = null;

    /**
     * @Assert\Length(max=50)
     * @ORM\Column(type="string", nullable=true, length=50)
     */
    private ?string $offerLanguage = null;

    /**
     * @Assert\Length(max=50)
     * @ORM\Column(type="string", nullable=true, length=50)
     */
    private ?string $offerLanguageIsoCodeThreeDigit = null;

    /**
     * @Assert\NotNull()
     * @ORM\Column(type="integer")
     */
    private int $salaryMin = 0;

    /**
     * @Assert\NotNull()
     * @ORM\Column(type="integer")
     */
    private int $salaryMax = 0;

    /**
     * @Assert\NotNull()
     * @ORM\Column(type="integer")
     */
    private int $salaryAverage = 0;

    /**
     * @Assert\NotNull()
     * @ORM\Column(type="boolean")
     */
    private bool $remoteJobMentioned = false;

    /**
     * @var array|null $keywords
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $keywords = null;

    /**
     * @var ArrayCollection<string, JobOfferExtraction>
     * @ORM\ManyToMany(targetEntity=JobOfferExtraction::class, inversedBy="jobSearchResults", cascade={"persist"})
     * @ORM\JoinTable(name="job_offer_extraction_job_search_result")
     */
    private $extractions;

    /**
     * @var ArrayCollection<string, Location>
     * @ORM\ManyToMany(targetEntity=Location::class, inversedBy="jobSearchResults", cascade={"persist"})
     * @ORM\JoinTable(name="job_search_result_location")
     */
    private $locations;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="jobOffers", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Company $company = null;

    /**
     * @ORM\ManyToOne(targetEntity=CompanyBranch::class, inversedBy="jobSearchResults", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private ?CompanyBranch $companyBranch = null;

    /**
     * @ORM\OneToMany(targetEntity=ExtractionKeyword2Offer::class, mappedBy="jobOffer", orphanRemoval=true)
     */
    private $extractionKeyword;

    /**
     * @ORM\ManyToOne(targetEntity=Email::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Email $email = null;

    /**
     * @ORM\ManyToOne(targetEntity=JobOfferExtraction::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private JobOfferExtraction $firstTimeFoundExtraction;

    public function __construct()
    {
        $this->created           = new DateTime();
        $this->modified          = new DateTime();
        $this->locations         = new ArrayCollection();
        $this->extractionKeyword = new ArrayCollection();
        $this->extractions       = new ArrayCollection();
    }

    /**
     * @return int|null
     */
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

    /**
     * @return string
     */
    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    /**
     * @param string $jobTitle
     */
    public function setJobTitle(string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }

    /**
     * @return string
     */
    public function getJobDescription(): string
    {
        return $this->jobDescription;
    }

    /**
     * Would be better to limit the field on queryBuilder, but it's not so simple,
     * This might still cause memory issues but at least will allow to fetch data faster on frontend
     *
     * @return string
     */
    public function getShortJobDescription(): string
    {
        return StringTypeProcessor::substrAndKeepHtmlTag($this->jobDescription, self::SHORT_DESCRIPTION_MAX_LENGTH);
    }

        /**
     * @param string $jobDescription
     */
    public function setJobDescription(string $jobDescription): void
    {
        $this->jobDescription = $jobDescription;
    }

    /**
     * @return string
     */
    public function getJobOfferUrl(): string
    {
        return $this->jobOfferUrl;
    }

    /**
     * @param string $jobOfferUrl
     */
    public function setJobOfferUrl(string $jobOfferUrl): void
    {
        $this->jobOfferUrl = $jobOfferUrl;
    }

    /**
     * @return string
     */
    public function getJobOfferHost(): string
    {
        return $this->jobOfferHost;
    }

    /**
     * @param string $jobOfferHost
     */
    public function setJobOfferHost(string $jobOfferHost): void
    {
        $this->jobOfferHost = $jobOfferHost;
    }

    /**
     * @return int
     */
    public function getSalaryMin(): int
    {
        return $this->salaryMin;
    }

    /**
     * @param int $salaryMin
     */
    public function setSalaryMin(int $salaryMin): void
    {
        $this->salaryMin = $salaryMin;
    }

    /**
     * @return int
     */
    public function getSalaryMax(): int
    {
        return $this->salaryMax;
    }

    /**
     * @param int $salaryMax
     */
    public function setSalaryMax(int $salaryMax): void
    {
        $this->salaryMax = $salaryMax;
    }

    /**
     * @return int
     */
    public function getSalaryAverage(): int
    {
        return $this->salaryAverage;
    }

    /**
     * @param int $salaryAverage
     */
    public function setSalaryAverage(int $salaryAverage): void
    {
        $this->salaryAverage = $salaryAverage;
    }

    /**
     * @return array|null
     */
    public function getMentionedHumanLanguages(): ?array
    {
        return $this->mentionedHumanLanguages;
    }

    /**
     * @param array|null $mentionedHumanLanguages
     */
    public function setMentionedHumanLanguages(?array $mentionedHumanLanguages): void
    {
        $this->mentionedHumanLanguages = $mentionedHumanLanguages;
    }

    /**
     * @return bool
     */
    public function isRemoteJobMentioned(): bool
    {
        return $this->remoteJobMentioned;
    }

    /**
     * @param bool $remoteJobMentioned
     */
    public function setRemoteJobMentioned(bool $remoteJobMentioned): void
    {
        $this->remoteJobMentioned = $remoteJobMentioned;
    }

    /**
     * @return DateTime|null
     */
    public function getJobPostedDateTime(): ?DateTime
    {
        return $this->jobPostedDateTime;
    }

    /**
     * @param DateTime|null $jobPostedDateTime
     */
    public function setJobPostedDateTime(?DateTime $jobPostedDateTime): void
    {
        $this->jobPostedDateTime = $jobPostedDateTime;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getModified(): ?\DateTime
    {
        return $this->modified;
    }

    public function setModified(\DateTime $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    /**
     * @param array|null $keywords
     */
    public function setKeywords(?array $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * @param array|null $keywords
     */
    public function mergeKeywords(?array $keywords): void
    {
        if (empty($keywords)) {
            return;
        }

        if (empty($this->keywords)) {
            $this->keywords = $keywords;
            return;
        }

        $this->keywords = array_unique([
            ...$this->keywords,
            ...$keywords
        ]);
    }

    /**
     * @return string|null
     */
    public function getOfferLanguage(): ?string
    {
        return $this->offerLanguage;
    }

    /**
     * @param string|null $offerLanguage
     */
    public function setOfferLanguage(?string $offerLanguage): void
    {
        $this->offerLanguage = $offerLanguage;
    }

    /**
     * @return Collection<JobOfferExtraction>
     */
    public function getExtractions(): Collection
    {
        return $this->extractions;
    }

    /**
     * Check if the job offer belongs to this extraction or not,
     * Offer can belong to multiple extraction so this checks if on of the extraction is the one
     * provided as the param
     *
     * @param JobOfferExtraction $checkedExtraction
     *
     * @return bool
     */
    public function belongsToExtraction(JobOfferExtraction $checkedExtraction): bool
    {
        foreach ($this->getExtractions() as $boundExtraction) {
            if ($boundExtraction->getId() === $checkedExtraction->getId()) {
                return true;
            }

        }
        return false;
    }

    /**
     * @return int
     */
    public function getExtractionsCount(): int
    {
        return count($this->getExtractions());
    }

    /**
     * @param array $extractions
     *
     * @return $this
     */
    public function setExtraction(array $extractions): self
    {
        $this->extractions = $extractions;

        return $this;
    }

    /**
     * @param JobOfferExtraction $extraction
     *
     * @return $this
     */
    public function addExtraction(JobOfferExtraction $extraction): self
    {
        if (!$this->extractions->contains($extraction)) {
            $this->extractions[] = $extraction;
        }

        return $this;
    }

    /**
     * Will return the locations as plain strings
     *
     * @return array
     */
    public function getLocationsAsStrings(): array
    {
        $locations = array_map(
            fn(Location $location) => $location->getName(),
            $this->locations->getValues(),
        );

        return $locations;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations[] = $location;
        }

        return $this;
    }


    /**
     * @param Location[] $locations
     */
    public function addLocations(array $locations): void
    {
        foreach ($locations as $location) {
            $this->addLocation($location);
        }
    }

    /**
     * Will return only the first location or null if there is no relation to location
     *
     * @return Location|null
     */
    public function getFirstLocation(): ?Location
    {
        if( empty($this->locations) ){
            return null;
        }

        return $this->locations->first();
    }

    /**
     * @return bool
     */
    public function hasLocation(): bool
    {
        return !$this->getLocations()->isEmpty();
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getCompanyBranch(): ?CompanyBranch
    {
        return $this->companyBranch;
    }

    public function setCompanyBranch(?CompanyBranch $companyBranch): self
    {
        $this->companyBranch = $companyBranch;

        return $this;
    }

    /**
     * @return Collection|ExtractionKeyword2Offer[]
     */
    public function getExtractionKeyword(): Collection
    {
        return $this->extractionKeyword;
    }

    public function addExtractionKeyword(ExtractionKeyword2Offer $extractionKeyword): self
    {
        if (!$this->extractionKeyword->contains($extractionKeyword)) {
            $this->extractionKeyword[] = $extractionKeyword;
            $extractionKeyword->setJobOffer($this);
        }

        return $this;
    }

    public function removeExtractionKeyword(ExtractionKeyword2Offer $extractionKeyword): self
    {
        if ($this->extractionKeyword->removeElement($extractionKeyword)) {
            // set the owning side to null (unless already changed)
            if ($extractionKeyword->getJobOffer() === $this) {
                $extractionKeyword->setJobOffer(null);
            }
        }

        return $this;
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

    public function getFirstTimeFoundExtraction(): JobOfferExtraction
    {
        return $this->firstTimeFoundExtraction;
    }

    public function setFirstTimeFoundExtraction(JobOfferExtraction $firstTimeFoundExtraction): self
    {
        $this->firstTimeFoundExtraction = $firstTimeFoundExtraction;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOfferLanguageIsoCodeThreeDigit(): ?string
    {
        return $this->offerLanguageIsoCodeThreeDigit;
    }

    /**
     * @param string|null $offerLanguageIsoCodeThreeDigit
     */
    public function setOfferLanguageIsoCodeThreeDigit(?string $offerLanguageIsoCodeThreeDigit): void
    {
        $this->offerLanguageIsoCodeThreeDigit = $offerLanguageIsoCodeThreeDigit;
    }

    /**
     * @return string
     */
    public function getAsMd5(): string
    {
        return md5(
              trim(mb_strtolower($this->getJobTitle()))
            . trim(mb_strtolower($this->getCompany()->getName()))
            . trim(mb_strtolower($this->getJobOfferUrl()))
        );
    }

}
