<?php
namespace JobSearcher\Service\JobService\Resolver\Traits;

use JobSearcher\Service\JobService\Resolver\ParametersEnum;

/**
 * Extracts html content from delivered parameters
 */
trait HtmlContentAwareTrait
{
    /**
     * @param array $parameters
     *
     * @return null|string
     */
    public function getHtmlContent(array $parameters): ?string
    {
        return $parameters[ParametersEnum::HTML_CONTENT->name];
    }
}