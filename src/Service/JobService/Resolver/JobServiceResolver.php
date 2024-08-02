<?php

namespace JobSearcher\Service\JobService\Resolver;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseSearchUriConfigurationDto;
use JobSearcher\Service\Kernel\KernelService;
use JobSearcher\Service\TypeProcessor\ArrayTypeProcessor;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Base class for resolving values of each of the Job Services
 */
abstract class JobServiceResolver
{
    /**
     * @var KernelInterface $kernel
     */
    protected readonly KernelInterface $kernel;

    /**
     * This construct should remain and contain some widely accessible code,
     * meaning for example Container as it will allow obtaining other services in child classes.
     *
     * This approach also ensures that there will be no need to set some extra properties to the class
     * whenever the resolver gets initialized
     */
    final public function __construct()
    {
        $this->kernel = KernelService::getKernel();
    }

    /**
     * Will initialize any necessary properties or variables once the constructor gets called
     */
    abstract public function init(): void;

    /**
     * Will return the distance key created from the location distance mapping (from yml).
     *
     * @param int|null                      $distance
     * @param BaseSearchUriConfigurationDto $configurationDto
     *
     * @return int|null
     */
    protected function getDistanceKey(?int $distance, BaseSearchUriConfigurationDto $configurationDto): ?int
    {
        $allowedDistances = $configurationDto->getLocationDistanceConfiguration()->getAllowedDistances();

        if (
                empty($distance)
            ||  empty($allowedDistances)
        ) {
            return null;
        }

        $distanceKey = ArrayTypeProcessor::getKeyForClosestNumber($distance, $allowedDistances);

        return $distanceKey;
    }
}
