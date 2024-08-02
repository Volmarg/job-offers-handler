<?php

namespace JobSearcher\Entity\Extraction;

use DateTime;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Extraction\ExtractionKeyword2OfferRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ExtractionKeyword2OfferRepository::class)
 * @ORM\Table(name="extraction_keyword_2_offer")
 */
class ExtractionKeyword2Offer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity=JobOfferExtraction::class, inversedBy="keywords2Offers")
     * @ORM\JoinColumn(nullable=false)
     */
    private JobOfferExtraction $extraction;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTime $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?DateTime $modified;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $keyword;

    /**
     * @ORM\ManyToOne(targetEntity=JobSearchResult::class, inversedBy="extractionKeyword")
     * @ORM\JoinColumn(nullable=false)
     */
    private JobSearchResult $jobOffer;

    public function __construct()
    {
        $this->created  = new DateTime();
        $this->modified = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExtraction(): ?JobOfferExtraction
    {
        return $this->extraction;
    }

    public function setExtraction(?JobOfferExtraction $extraction): self
    {
        $this->extraction = $extraction;

        return $this;
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

    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    public function setModified(DateTime $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): self
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getJobOffer(): ?JobSearchResult
    {
        return $this->jobOffer;
    }

    public function setJobOffer(?JobSearchResult $jobOffer): self
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }
}
