<?php

namespace JobSearcher\Entity\Extraction;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JobSearcher\Command\Helper\MergeMultipleJobExtractionsCommand;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use Doctrine\ORM\Mapping as ORM;
use JobSearcher\Service\JobSearch\Command\Extractor\ExtractorInterface;

/**
 * @ORM\Entity(repositoryClass=JobOfferExtractionRepository::class)
 */
class JobOfferExtraction
{
    /**
     * Either new entry or it's in progress
     */
    public const STATUS_IN_PROGRESS = "IN_PROGRESS";

    /**
     * Something went wrong and could not extract data at all
     */
    public const STATUS_FAILED = "FAILED";

    /**
     * Everything has been fully extracted
     */
    public const STATUS_IMPORTED = "IMPORTED";

    /**
     * Few extractions were merged into one (most likely via {@see MergeMultipleJobExtractionsCommand})
     */
    public const STATUS_MERGED = "MERGED";

    /**
     * Something went wrong but managed to continue and extracted part of the data
     */
    public const STATUS_PARTIALLY_IMPORTED = "PARTIALLY_IMPORTED";

    /**
     * Can happen when for example {@see MergeMultipleJobExtractionsCommand} is used
     */
    public const PAGINATION_COUNT_UNKNOWN = -1;

    public const TYPE_ALL = "ALL";

    public const TYPE_SINGLE = "SINGLE";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="array")
     */
    private array $keywords = [];

    /**
     * @ORM\Column(type="integer")
     */
    private int $paginationPagesCount;

    /**
     * @ORM\Column(type="array")
     */
    private array $sources;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $modified;

    /**
     * This serves only as information "which configuration were used in process".
     * It does not mean that each of searched keyword provides offers for each of the configuration.
     * For that see: {@see}
     *
     * @ORM\Column(type="array")
     */
    private array $configurations = [];

    /**
     * @ORM\Column(type="text")
     */
    private string $type;

    /**
     * @ORM\Column(type="integer")
     */
    private int $extractionCount = 0;

    /**
     * This gets updated only when:
     * - {@see ExtractorInterface} is done,
     * - Exception gets thrown,
     *
     *
     * @ORM\Column(type="float")
     */
    private int $percentageDone = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $newOffersCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $boundOffersCount = 0;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $status;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $distance = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $offersLimit = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $location = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $country = null;

    /**
     * This is just a flag for filtering, not and entity field
     *
     * @var bool
     */
    private bool $filterOffersWithoutCompanyBranch = false;

    /**
     * @var ArrayCollection<string, JobSearchResult>
     * @ORM\ManyToMany(targetEntity=JobSearchResult::class, inversedBy="extractions", cascade={"persist"})
     */
    private $jobSearchResults;

    /**
     * @ORM\OneToMany(targetEntity=ExtractionKeyword2Offer::class, mappedBy="extraction", orphanRemoval=true, cascade={"persist"})
     */
    private $keywords2Offers;

    /**
     * @ORM\OneToOne(targetEntity=Extraction2AmqpRequest::class, mappedBy="extraction", cascade={"persist", "remove"})
     */
    private $extraction2AmqpRequest;

    /**
     * @ORM\OneToMany(targetEntity=ExtractionKeyword2Configuration::class, mappedBy="extraction", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $keyword2Configurations;

    /**
     * This is not always going to be set, and that's ok, saving only the most hard-to-catch / debug errors
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $errorMessage = null;

    /**
     * This is not always going to be set, and that's ok, saving only the most hard-to-catch / debug errors
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $errorTrace = null;

    public function __construct()
    {
        $this->created          = new DateTime();
        $this->modified         = new DateTime();
        $this->jobSearchResults = new ArrayCollection();
        $this->keywords2Offers  = new ArrayCollection();
        $this->keyword2Configurations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getPaginationPagesCount(): ?int
    {
        return $this->paginationPagesCount;
    }

    public function setPaginationPagesCount(int $paginationPagesCount): self
    {
        $this->paginationPagesCount = $paginationPagesCount;

        return $this;
    }

    /**
     * @return array
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @param array $sources
     */
    public function setSources(array $sources): void
    {
        $this->sources = $sources;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getConfigurations(): ?array
    {
        return $this->configurations;
    }

    public function setConfigurations(array $configurations): self
    {
        $this->configurations = $configurations;

        return $this;
    }

    /**
     * @param string $configuration
     */
    public function addConfiguration(string $configuration): void
    {
        if (in_array($configuration, $this->configurations)) {
            return;
        }

        $this->configurations[] = $configuration;
    }

    /**
     * @return int
     */
    public function getExtractionCount(): int
    {
        return $this->extractionCount;
    }

    public function setExtractionCount(int $extractionCount): self
    {
        $this->extractionCount = $extractionCount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection|JobSearchResult[]
     */
    public function getJobSearchResults(): Collection
    {
        $offers = $this->jobSearchResults;
        if ($this->isFilterOffersWithoutCompanyBranch()) {
            $offers = $this->jobSearchResults->filter(fn(JobSearchResult $result) => !is_null($result->getCompanyBranch()));
        }

        return $offers;
    }

    /**
     * @return int|null
     */
    public function getDistance(): ?int
    {
        return $this->distance;
    }

    /**
     * @param int|null $distance
     */
    public function setDistance(?int $distance): void
    {
        $this->distance = $distance;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @param string|null $location
     */
    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     */
    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function addJobSearchResult(JobSearchResult $jobSearchResult): self
    {
        /**
         * This is added due to prediction that the `contains` method is not making proper check, this is then leading
         * to breaking of sql due to duplicated entries
         */
        $duplicatedOffers = $this->jobSearchResults->filter(function(JobSearchResult $containedOffer) use ($jobSearchResult){
             return (
                    !empty($jobSearchResult->getId())
                &&  !empty($containedOffer->getId())
                &&  $containedOffer->getId() == $jobSearchResult->getId()
             );
        });

        if (
                empty($duplicatedOffers)
            &&  !$this->jobSearchResults->contains($jobSearchResult)
        ) {
            $this->jobSearchResults[] = $jobSearchResult;
        }

        return $this;
    }

    /**
     * @return Collection|ExtractionKeyword2Offer[]
     */
    public function getKeywords2Offers(): Collection
    {
        return $this->keywords2Offers;
    }

    /**
     * Returns count of offers found for given keyword.
     *
     * @param string $keyword
     *
     * @return int
     */
    public function countOffersForKeyword(string $keyword): int
    {
        $count = 0;
        foreach ($this->getKeywords2Offers() as $keywords2Offer) {
            if ($keywords2Offer->getKeyword() === $keyword) {
                $count++;
            }
        }

        return $count;
    }

    public function addKeywords2Offer(ExtractionKeyword2Offer $keywords2Offer): self
    {
        if (!$this->keywords2Offers->contains($keywords2Offer)) {
            $this->keywords2Offers[] = $keywords2Offer;
            $keywords2Offer->setExtraction($this);
        }

        return $this;
    }

    public function removeKeywords2Offer(ExtractionKeyword2Offer $keywords2Offer): self
    {
        if ($this->keywords2Offers->removeElement($keywords2Offer)) {
            // set the owning side to null (unless already changed)
            if ($keywords2Offer->getExtraction() === $this) {
                $keywords2Offer->setExtraction(null);
            }
        }

        return $this;
    }

    public function getExtraction2AmqpRequest(): ?Extraction2AmqpRequest
    {
        return $this->extraction2AmqpRequest;
    }

    public function setExtraction2AmqpRequest(Extraction2AmqpRequest $extraction2AmqpRequest): self
    {
        // set the owning side of the relation if necessary
        if ($extraction2AmqpRequest->getExtraction() !== $this) {
            $extraction2AmqpRequest->setExtraction($this);
        }

        $this->extraction2AmqpRequest = $extraction2AmqpRequest;

        return $this;
    }

    /**
     * @return int
     */
    public function getNewOffersCount(): int
    {
        return $this->newOffersCount;
    }

    /**
     * @param int $newOffersCount
     */
    public function setNewOffersCount(int $newOffersCount): void
    {
        $this->newOffersCount = $newOffersCount;
    }

    /**
     * @return int
     */
    public function getBoundOffersCount(): int
    {
        return $this->boundOffersCount;
    }

    /**
     * @param int $boundOffersCount
     */
    public function setBoundOffersCount(int $boundOffersCount): void
    {
        $this->boundOffersCount = $boundOffersCount;
    }

    /**
     * Returns ids ofs all offers for this extraction
     *
     * @return array
     */
    public function getOfferIds(): array
    {
        $ids = array_map(
            fn(JobSearchResult $offer) => $offer->getId(),
            $this->getJobSearchResults()->getValues()
        );

        return $ids;
    }

    /**
     * @return DateTime
     */
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    /**
     * @param DateTime $modified
     */
    public function setModified(DateTime $modified): void
    {
        $this->modified = $modified;
    }

    /**
     * @return int
     */
    public function getPercentageDone(): int
    {
        return $this->percentageDone;
    }

    /**
     * @param int $percentageDone
     */
    public function setPercentageDone(int $percentageDone): void
    {
        $this->percentageDone = $percentageDone;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return Collection|ExtractionKeyword2Configuration[]
     */
    public function getKeyword2Configurations(): Collection
    {
        return $this->keyword2Configurations;
    }

    public function addKeyword2Configuration(ExtractionKeyword2Configuration $keyword2Configuration): self
    {
        if (!$this->keyword2Configurations->contains($keyword2Configuration)) {
            $this->keyword2Configurations[] = $keyword2Configuration;
            $keyword2Configuration->setExtraction($this);
        }

        return $this;
    }

    public function removeKeyword2Configuration(ExtractionKeyword2Configuration $keyword2Configuration): self
    {
        if ($this->keyword2Configurations->removeElement($keyword2Configuration)) {
            // set the owning side to null (unless already changed)
            if ($keyword2Configuration->getExtraction() === $this) {
                $keyword2Configuration->setExtraction(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @param string|null $errorMessage
     */
    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return string|null
     */
    public function getErrorTrace(): ?string
    {
        return $this->errorTrace;
    }

    /**
     * @param string|null $errorTrace
     */
    public function setErrorTrace(?string $errorTrace): void
    {
        $this->errorTrace = $errorTrace;
    }

    public function isFilterOffersWithoutCompanyBranch(): bool
    {
        return $this->filterOffersWithoutCompanyBranch;
    }

    public function setFilterOffersWithoutCompanyBranch(bool $filterOffersWithoutCompanyBranch): void
    {
        $this->filterOffersWithoutCompanyBranch = $filterOffersWithoutCompanyBranch;
    }

    /**
     * Checking if something could possibly crash etc.
     * In rare cases it COULD really be running short, but that's highly unlikely.
     *
     * Value can't be too high because it can happen that existing offers will get bound,
     * and that will be rather very fast.
     *
     * It might turn out that this check might need to be removed in future on faster connection speed etc.
     *
     * @return bool
     */
    public function isValidRunTime(): bool
    {
        $minRunSeconds   = 30;
        $minRunTimestamp = (clone $this->getCreated())->modify("+{$minRunSeconds} SECOND");

        return ((new DateTime())->getTimestamp() > $minRunTimestamp->getTimestamp());
    }

    public function getOffersLimit(): ?int
    {
        return $this->offersLimit;
    }

    public function setOffersLimit(?int $offersLimit): void
    {
        $this->offersLimit = $offersLimit;
    }

    public function isOffersLimitSet(): bool
    {
        return !is_null($this->offersLimit);
    }
}
