<?php

namespace JobSearcher\Entity\Extraction;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="extraction_keyword_2_configuration")
 */
class ExtractionKeyword2Configuration
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=JobOfferExtraction::class, inversedBy="keyword2Configurations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $extraction;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $keyword;

    /**
     * @ORM\Column(type="array")
     */
    private $configurations = [];

    /**
     * @ORM\Column(type="array")
     */
    private $expectedConfigurations = [];

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

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): self
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfigurations(): array
    {
        return $this->configurations;
    }

    /**
     * @param array $configurations
     */
    public function setConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    /**
     * @return array
     */
    public function getExpectedConfigurations(): array
    {
        return $this->expectedConfigurations;
    }

    /**
     * @param array $expectedConfigurations
     */
    public function setExpectedConfigurations(array $expectedConfigurations): void
    {
        $this->expectedConfigurations = $expectedConfigurations;
    }

}
