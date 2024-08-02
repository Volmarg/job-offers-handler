<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Doctrine\Type\ArrayType;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustHavePhoneModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (self::getFilter()->isMustHavePhone()) {
            $queryBuilder->andWhere("cb.phoneNumbers != :emptyPhoneNumbers")
                         ->setParameter("emptyPhoneNumbers", ArrayType::EMPTY_ARRAY_VALUE);
        }
    }
}