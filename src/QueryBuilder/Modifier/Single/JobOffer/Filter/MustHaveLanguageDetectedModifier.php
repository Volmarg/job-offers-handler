<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use Doctrine\ORM\QueryBuilder;
use JobSearcher\Doctrine\Type\ArrayType;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MustHaveLanguageDetectedModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        if (!self::getFilter()->isIncludeJobOffersWithoutHumanLanguagesMentioned()) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        "jbs.mentionedHumanLanguages NOT IN(:nonEmptyLanguages)",
                        "jbs.offerLanguage IS NOT NULL"
                    )
                )
                ->setParameter("nonEmptyLanguages", [ArrayType::SERIALIZED_EMPTY_ARRAY_VALUE, ArrayType::EMPTY_ARRAY_VALUE]);
        }
    }
}