<?php

namespace JobSearcher\Exception\Configuration\DomHtml;

use Exception;
use JobSearcher\DTO\JobService\SearchConfiguration\DomHtml\MainConfigurationDto;

/**
 * Indicates that dom configuration is missing:
 * This is explicitly for {@see MainConfigurationDto::getDomElementSelectorAndAttributeConfiguration}
 */
class ConfigurationNotFoundException extends Exception
{

}