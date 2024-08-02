<?php

namespace JobSearcher\Service\JobService\Resolver\Traits;

use LogicException;

/**
 * Ensures that the key is set in parameter array at all
 */
trait EnsureParameterKeySetTrait
{
    /**
     * Makes sure that given key is set in parameters array
     *
     * @param array  $parameters
     * @param string $key
     */
    public function ensureSet(array $parameters, string $key): void
    {
        if (!array_key_exists($key, $parameters)) {
            throw new LogicException("Parameters array is missing key (with its value): " . $key);
        }
    }
}