<?php

namespace JobSearcher\Service\JobSearch;

use CompanyDataProvider\CompanyDataProviderBundle;
use JobSearcher\DTO\JobService\NewAndExistingOffersDto;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Service\Extraction\Offer\ExtractionProgressDeciderService;

/**
 * Handles limiting the amount of returned offers.
 * This is purely a hack. The thing is that implementing this more properly will be very time-consuming.
 * With above statement in mind it's not worth it.
 *
 * It was never planned to use any limiter at all, everything in mind was "search through all configs"
 * validate toward the configs etc. Now after calculating all the fees etc. it turned out it gets a bit expensive
 * so wanted to provide a way to make users use the platform cheaper.
 *
 * Downside is:
 * (-) extraction run will take long anyway because it will go over all the configured services,
 * (-) offers above the limit are just discarded
 *   - in future these could be saved tho but if next run binds them it would need to search for emails etc., as this will speed things up
 * (-) some of the rules from {@see ExtractionProgressDeciderService} need to be skipped for this to work,
 *     meaning higher risk that money would be incorrectly returned to user, but that's a risk that has to be tested here,
 *
 * Pros is:
 * (+) it's quicker to implement,
 * (+) there aren't much of costs hidden behind with how it all works (first offers are fetched from all sources then it looks for data for them)
 * (+) data searching with {@see CompanyDataProviderBundle} takes the long so speed wise should be fine
 */
class OfferExtractionLimiterService
{
    /**
     * Represents count of offers found in current run, it's added as static, in order to track offers
     * per whole run itself. This way there is no need to have special prop on {@see JobOfferExtraction} etc.
     *
     * @var int $OFFERS_COUNT
     */
    private static int $OFFERS_COUNT = 0;

    /**
     * Want to be pro-user, thus returning some extra offers (above what they paid for),
     * also that helps in case when some offers saving would be rejected for whatever reason,
     * don't have to do this but just want to prevent problems caused by disabling the checks
     * in {@see ExtractionProgressDeciderService}.
     *
     * The entire decider logic could be made differently, but it's just not worth it now while not
     * knowing if project will work out. There were some mistakes made when planning the project and
     * that's the price to pay, we go with what we have, it's not the best, it's neither super bad.
     */
    private const PERCENTAGE_OFFERS_BONUS = 20;

    /**
     * This is special limiter based on the costs calculation and all the runs that were made on dev before
     * project was released. Costs were calculated estimating that avg. amount of found offers is 140.
     * It can happen that in some cases it's more than that like even 200-300, that's undesired for now
     * because the costs will be too high. So in theory if {@see JobOfferExtraction::$offersLimit} is null,
     * then it's limitless, but in practice for initial run it's going to hard-capped to reduce costs.
     *
     * This can be safely removed once product is self-sufficient.
     * This is also special case, which should NEVER affect: {@see ExtractionProgressDeciderService}
     * - absolutely NEVER, as user is paying the highest price here and refund must work as intended
     */
    private const LIMITLESS_MAX_OFFERS_CAP = 999999; # open-source (was 90-140)

    /**
     * Adding even more offers than actually it's limited since it's easier to make it this way,
     * also it's pro-user then, he might get more offers thanks to this.
     *
     * @param NewAndExistingOffersDto $dto
     * @param int|null                $limit
     *
     * @return NewAndExistingOffersDto
     */
    public static function getLimitedOffers(NewAndExistingOffersDto $dto, ?int $limit): NewAndExistingOffersDto
    {
        if (is_null($limit)) {
            $limit = self::LIMITLESS_MAX_OFFERS_CAP;
        }

        $usedLimit = (int)($limit + (self::PERCENTAGE_OFFERS_BONUS / 100 * $limit));

        self::$OFFERS_COUNT += $dto->countAllOffers();
        if (self::$OFFERS_COUNT < $usedLimit) {
            return $dto;
        }

        if (self::$OFFERS_COUNT > $usedLimit) {
            $aboveLimit = self::$OFFERS_COUNT - $usedLimit;
            self::handleOffersAboveLimit($dto, $aboveLimit);
        }

        return $dto;
    }

    /**
     * The search results are grouped inside DTO per config, so the easiest way is to first go over the dto and add them per config
     * and this is what happens before current method is called.
     *
     * Now in here, need to go separately over search results of newly found offers and already existing (bound) offers,
     * and exclude the offers that are above the limit.
     *
     * @param NewAndExistingOffersDto $dto
     * @param int                     $aboveLimit
     */
    private static function handleOffersAboveLimit(NewAndExistingOffersDto $dto, int $aboveLimit): void
    {
        $searchResults = $dto->getAllSearchResultDtos();
        foreach ($searchResults as $index => $offer) {
            if ($aboveLimit > 0) {
                unset($searchResults[$index]);
                $aboveLimit--;
                continue;
            }

            break;
        }
        $dto->setAllSearchResultDtos($searchResults);

        $existingEntities = $dto->getExistingOfferEntities();
        foreach ($existingEntities as $index => $existingOffer) {
            if ($aboveLimit > 0) {
                unset($existingEntities[$index]);
                $aboveLimit--;
                continue;
            }

            break;
        }
        $dto->setExistingOfferEntities($existingEntities);
    }
}