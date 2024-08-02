<?php

namespace JobSearcher\QueryBuilder\Modifier\Single\JobOffer\Filter;

use DateTime;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use JobSearcher\Exception\QueryBuilder\DataNotFoundException;
use JobSearcher\QueryBuilder\Interfaces\SingleModifierInterface;

class MatchPostedDateTimeRangeModifier extends BaseFilterModifier implements SingleModifierInterface
{

    /**
     * {@inheritDoc}
     * @throws DataNotFoundException
     */
    public static function apply(QueryBuilder $queryBuilder)
    {
        $exprJobOffersWithoutPostedDateTime = $queryBuilder->expr()->isNull("jbs.jobPostedDateTime");
        $exprJobOffersWithPostedDateTime    = $queryBuilder->expr()->isNotNull("jbs.jobPostedDateTime");

        if(
                !empty(self::getFilter()->getJobPostedDateTimeFilterDto()->getFirstTimestamp())
            ||  !empty(self::getFilter()->getJobPostedDateTimeFilterDto()->getSecondTimestamp())
        ){

            // this format must be provided else generated dates have no `''` in query thus it crashes
            $firstDate  = "'" . (new DateTime())->setTimestamp((int)self::getFilter()->getJobPostedDateTimeFilterDto()->getFirstTimestamp())->format("Y-m-d H:i:s")  . "'";
            $secondDate = "'" . (new DateTime())->setTimestamp((int)self::getFilter()->getJobPostedDateTimeFilterDto()->getSecondTimestamp())->format("Y-m-d H:i:s") . "'";

            if (empty(self::getFilter()->getJobPostedDateTimeFilterDto()->getSecondTimestamp())) {
                $postedRangeExpr = new Comparison(
                    "jbs.jobPostedDateTime",
                    self::getFilter()->getJobPostedDateTimeFilterDto()->getComparisonOperator(),
                    $firstDate
                );
            } elseif (empty(self::getFilter()->getJobPostedDateTimeFilterDto()->getFirstTimestamp())) {
                $postedRangeExpr = new Comparison(
                    "jbs.jobPostedDateTime",
                    self::getFilter()->getJobPostedDateTimeFilterDto()->getComparisonOperator(),
                    $secondDate
                );
            } else {
                $postedRangeExpr = $queryBuilder->expr()->between(
                    "jbs.jobPostedDateTime",
                    $firstDate,
                    $secondDate,
                );
            }

            if (self::getFilter()->isIncludeJobOfferWithoutPostedDateTime()) {
                $exprTimestamps = $queryBuilder->expr()->orX(
                    $postedRangeExpr,
                    $exprJobOffersWithoutPostedDateTime,
                );
                $queryBuilder->andWhere($exprTimestamps);
            }else{
                $queryBuilder->andWhere($postedRangeExpr);
            }
        }

        if (
                empty(self::getFilter()->getJobPostedDateTimeFilterDto()->getFirstTimestamp())
            &&  empty(self::getFilter()->getJobPostedDateTimeFilterDto()->getSecondTimestamp())
        ) {
            if (self::getFilter()->isIncludeJobOfferWithoutPostedDateTime()) {
                $queryBuilder->expr()->orX(
                    $exprJobOffersWithoutPostedDateTime,
                    $exprJobOffersWithPostedDateTime,
                );
            }else{
                $queryBuilder->andWhere($exprJobOffersWithPostedDateTime);
            }

        }

    }
}