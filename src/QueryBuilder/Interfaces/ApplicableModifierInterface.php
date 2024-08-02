<?php

namespace JobSearcher\QueryBuilder\Interfaces;

use Doctrine\ORM\QueryBuilder;

/**
 * Provides interface for applying {@see QueryBuilder} modifications
 */
interface ApplicableModifierInterface
{
    /**
     * Apply query builder modifier
     *
     * @param QueryBuilder $queryBuilder
     */
    public static function apply(QueryBuilder $queryBuilder);
}