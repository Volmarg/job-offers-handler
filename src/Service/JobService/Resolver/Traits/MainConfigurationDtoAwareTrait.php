<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\DTO\JobService\SearchConfiguration\Api\MainConfigurationDto as ApiMainConfigurationDto;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto as DomMainConfigurationDto;
use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts main configuration dto from delivered parameters
 */
trait MainConfigurationDtoAwareTrait
{
    use EnsureParameterKeySetTrait;

    /**
     * @param array $parameters
     *
     * @return ApiMainConfigurationDto|DomMainConfigurationDto
     */
    public function getMainConfigurationDto(array $parameters): ApiMainConfigurationDto | DomMainConfigurationDto
    {
        $this->ensureSet($parameters, ParametersEnum::MAIN_CONFIGURATION_DTO->name);
        return $parameters[ParametersEnum::MAIN_CONFIGURATION_DTO->name];
    }
}