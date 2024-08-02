<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\Statistic\ContactEmailStatistic;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use JobSearcher\Entity\Company\Company;
use JobSearcher\Entity\Email\Email2Company;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;
use JobSearcher\QueryBuilder\Modifier\Single\BaseSingleModifier;

class MustNotHaveApplicationEmailModifier extends BaseSingleModifier implements SingleModifierInterface
{
    /**
     * {@inheritDoc}
     * @param QueryBuilder $queryBuilder
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->isNull("jsr.email"),
                    $queryBuilder->expr()->isNull("e2c.id"),
                ),
            )
        );

        $queryBuilder->join(Company::class, "c", Join::WITH, "c.id = jsr.company")
            ->leftJoin(Email2Company::class, "e2c", Join::WITH, "
            e2c.id = (
                SELECT MAX(e2c_one.id) FROM " . Email2Company::class . " as e2c_one 
                WHERE e2c_one.company = c.id
                AND e2c_one.forJobApplication = 1
           )
        ");
    }
}