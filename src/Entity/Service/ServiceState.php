<?php

namespace JobSearcher\Entity\Service;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JobSearcher\Repository\Service\ServiceStateRepository;

/**
 * Contains information about state of services that are being used in current project
 *
 * @ORM\Entity(repositoryClass=ServiceStateRepository::class)
 * @ORM\Table(name="service_state")
 */
class ServiceState
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
     * @ORM\Column(type="string", nullable=false, length=300)
     */
    private string $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $active = true;

    public function __construct()
    {
        $this->modified = new DateTime();
        $this->created  = new DateTime();
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     */
    public function setCreated(DateTime $created): void
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

}
