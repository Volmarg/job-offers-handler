<?php

namespace JobSearcher\Entity\Extraction;

use DateTime;
use JobSearcher\Entity\Storage\AmqpStorage;
use JobSearcher\Repository\Extraction\Extraction2AmqpRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Extraction2AmqpRequestRepository::class)
 */
class Extraction2AmqpRequest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $created;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTime $modified;

    /**
     * @ORM\OneToOne(targetEntity=JobOfferExtraction::class, inversedBy="extraction2AmqpRequest", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $extraction;

    /**
     * @ORM\OneToOne(targetEntity=AmqpStorage::class, inversedBy="extraction2AmqpRequest", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $amqpRequest;

    public function __construct()
    {
        $this->created = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExtraction(): ?JobOfferExtraction
    {
        return $this->extraction;
    }

    public function setExtraction(JobOfferExtraction $extraction): self
    {
        $this->extraction = $extraction;

        return $this;
    }

    public function getAmqpRequest(): ?AmqpStorage
    {
        return $this->amqpRequest;
    }

    public function setAmqpRequest(AmqpStorage $amqpRequest): self
    {
        $this->amqpRequest = $amqpRequest;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime
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

    public function setModified(?\DateTime $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

}
