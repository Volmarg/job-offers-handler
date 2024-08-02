<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts keywords from delivered parameters
 */
trait KeywordsAwareTrait
{
    use EnsureParameterKeySetTrait;

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function getKeywords(array $parameters): array
    {
        $this->ensureSet($parameters, ParametersEnum::KEYWORDS->name);
        return $parameters[ParametersEnum::KEYWORDS->name];
    }
}