<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MatchCountryNameModifier extends BaseFilterModifier implements SingleModifierInterface
{
    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (!empty(self::getFilter()->getCountryNames())) {
            $allCountryNames = self::getFilter()->getCountryNames();
            $queryBuilder->andWhere("l.country IN (:countryNames)")
                         ->setParameter("countryNames", $allCountryNames, Connection::PARAM_STR_ARRAY);
        }
    }

}