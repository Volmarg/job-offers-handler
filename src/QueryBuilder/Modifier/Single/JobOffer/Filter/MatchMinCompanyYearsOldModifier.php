<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MatchMinCompanyYearsOldModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (self::getFilter()->getCompanyMinYearsOld()) {

            $equalExpression = $queryBuilder->expr()
                                            ->gte("DATE_FORMAT(NOW(), '%Y') - (c.foundedYear)", ":companyMinYearsOld")
            ;
            if (!self::getFilter()->isIncludeOffersWithoutCompanyFoundedYear()) {
                $queryBuilder->andWhere($equalExpression)
                             ->andWhere("c.foundedYear IS NOT NULL");
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orX(
                    "c.foundedYear IS NULL",
                    $equalExpression
                ));
            }

            $queryBuilder->setParameter("companyMinYearsOld", self::getFilter()->getCompanyMinYearsOld());
        }
    }
}