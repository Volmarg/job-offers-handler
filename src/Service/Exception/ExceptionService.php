<?php

namespace JobSearcher\Service\Exception;

use Throwable;

class ExceptionService
{
    public static function isEntityManagerClosed(Throwable $e): bool
    {
        return str_contains($e->getMessage(), "EntityManager is closed");
    }
}