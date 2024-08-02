<?php

namespace JobSearcher\Entity\Location;

use DateTime;
use JobSearcher\Repository\Location\LocationDistanceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LocationDistanceRepository::class)
 */
class LocationDistance
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
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity=Location::class, inversedBy="locationDistances")
     * @ORM\JoinColumn(nullable=false)
     */
    private Location $firstLocation;

    /**
     * @ORM\ManyToOne(targetEntity=Location::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private Location $secondLocation;

    /**
     * @ORM\Column(type="float")
     */
    private $distance;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $offerServiceBased = false;

    public function __construct()
    {
        $this->created = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return Location
     */
    public function getFirstLocation(): Location
    {
        return $this->firstLocation;
    }

    /**
     * @param Location $firstLocation
     */
    public function setFirstLocation(Location $firstLocation): void
    {
        $this->firstLocation = $firstLocation;
    }

    /**
     * @return Location
     */
    public function getSecondLocation(): Location
    {
        return $this->secondLocation;
    }

    /**
     * @param Location $secondLocation
     */
    public function setSecondLocation(Location $secondLocation): void
    {
        $this->secondLocation = $secondLocation;
    }

    /**
     * @return bool
     */
    public function isOfferServiceBased(): bool
    {
        return $this->offerServiceBased;
    }

    /**
     * @param bool $offerServiceBased
     */
    public function setOfferServiceBased(bool $offerServiceBased): void
    {
        $this->offerServiceBased = $offerServiceBased;
    }

}
