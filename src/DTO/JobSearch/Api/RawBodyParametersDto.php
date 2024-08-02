<?php

namespace JobSearcher\DTO\JobSearch\Api;

/**
 * Represents raw body sent in request
 */
class RawBodyParametersDto
{

    /**
     * @var string $name
     */
    private string $name;

    /**
     * @var mixed $value
     */
    private mixed $value;

    /**
     * @var RawBodyParametersDto[] $children
     */
    private array $children;

    /**
     * @param string                 $name
     * @param string|bool|int|null   $value - null is set when there are children being set
     * @param RawBodyParametersDto[] $children
     */
    public function __construct(string $name, null|string|bool|int $value, array $children)
    {
        $this->name     = $name;
        $this->value    = $value;
        $this->children = $children;
    }

    /**
     * Will check if this dto has any children
     */
    public function hasChildren(): bool
    {
        return (count($this->children) > 0);
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
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * @return RawBodyParametersDto[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param RawBodyParametersDto[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

}