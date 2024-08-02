<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;
use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts {@see BaseSearchUriConfigurationDto} from delivered parameters
 */
trait BaseSearchUriConfigurationDtoAwareTrait
{
    use EnsureParameterKeySetTrait;

    /**
     * @param array $parameters
     *
     * @return BaseSearchUriConfigurationDto
     */
    public function getBaseSearchUriConfigurationDto(array $parameters): BaseSearchUriConfigurationDto
    {
        $this->ensureSet($parameters, ParametersEnum::BASE_SEARCH_URI_DTO->name);
        return $parameters[ParametersEnum::BASE_SEARCH_URI_DTO->name];
    }
}