<?php

namespace JobSearcher\Entity\Storage;

use JobSearcher\Entity\Extraction\Extraction2AmqpRequest;
use JobSearcher\Repository\Storage\AmqpStorageRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AmqpStorageRepository::class)
 */
class AmqpStorage
{

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $created;

    /**
     * @ORM\Column(type="datetime", nullable=true, columnDefinition="DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTime $modified;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", nullable=true, length=300)
     */
    private string $targetClass;

    /**
     * @ORM\Column(type="string", length=900)
     */
    private string $message;

    /**
     * @ORM\Column(type="string", unique=true, length=255)
     */
    private string $uniqueId;

    /**
     * @ORM\OneToOne(targetEntity=Extraction2AmqpRequest::class, mappedBy="amqpRequest", cascade={"persist", "remove"})
     */
    private $extraction2AmqpRequest;

    public function __construct()
    {
        $this->uniqueId = uniqid();
        $this->modified = new DateTime();
        $this->created  = new DateTime();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    /**
     * @param string $targetClass
     */
    public function setTargetClass(string $targetClass): void
    {
        $this->targetClass = $targetClass;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     */
    public function setUniqueId(string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
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

    public function getExtraction2AmqpRequest(): ?Extraction2AmqpRequest
    {
        return $this->extraction2AmqpRequest;
    }

    public function setExtraction2AmqpRequest(Extraction2AmqpRequest $extraction2AmqpRequest): self
    {
        // set the owning side of the relation if necessary
        if ($extraction2AmqpRequest->getAmqpRequest() !== $this) {
            $extraction2AmqpRequest->setAmqpRequest($this);
        }

        $this->extraction2AmqpRequest = $extraction2AmqpRequest;

        return $this;
    }

}
