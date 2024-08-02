<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustHaveCompanyAgeOldModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (self::getFilter()->isAgeRequired()) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->isNotNull("c.foundedYear")
                );
        }
    }
}