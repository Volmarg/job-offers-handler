<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Doctrine\Type\ArrayType;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustHaveEmailModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (self::getFilter()->isMustHaveMail()) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                    "jbs.email IS NOT NULL",
                    $queryBuilder->expr()->andX(
                        "e2c.id IS NOT NULL",
                        "e2c.forJobApplication = 1",
                    )
                ),
            );
        }
    }
}