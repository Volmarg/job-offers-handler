<?php

namespace JobSearcher\Entity\Email;

use DateTime;
use DateTimeInterface;
use JobSearcher\Repository\Email\EmailSourceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmailSourceRepository::class)
 */
class EmailSource
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
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

    public function __construct()
    {
        $this->created  = new DateTime();
        $this->modified = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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
