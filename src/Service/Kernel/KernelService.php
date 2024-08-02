<?php

namespace JobSearcher\Service\Kernel;

use JobSearcher\Kernel;
use JobSearcher\Service\Env\EnvReader;

/**
 * Related to {@see Kernel}
 */
class KernelService
{

    /**
     * Statically creates the kernel instance. This should avoid to be extended. This is not desired way of getting data
     * from container etc. Besides the RUNTIME container might not share data between container generated statically!
     *
     * @return Kernel
     */
    public static function getKernel(): Kernel
    {
        $kernel = new Kernel(EnvReader::getEnvironment(), EnvReader::isDebug());
        $kernel->boot();

        return $kernel;
    }
}