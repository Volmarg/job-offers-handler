<?php

namespace JobSearcher\Exception\QueryBuilder;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Indicates that there is no data stored under given key
 */
class DataNotFoundException extends Exception
{
    public function __construct(string $key)
    {
        parent::__construct("There is no key called {$key} in data array", Response::HTTP_NOT_FOUND);
    }
}