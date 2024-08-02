<?php

namespace JobSearcher\Service\JobSearch\Crawler;

/**
 * This service was added due to the issues with crawling still persisting,
 * Seems like this issue exists with parallel calls, for example 10 calls being made for the
 * same "keyword" toward the same service.
 *
 * If the calls are made to fast one after another then it often results in http response 4xx
 *
 * Yes there will be proxy in use so theoretically this could be avoided but want to reduce the
 * bans / rejections to the minimum - it could theoretically still happen that few users will
 * be crawling the service from same proxy ip.
 */
class DynamicDelayDecider
{

    /**
     * There is nothing fancy in here, just some rand,
     * Theoretically could make it more proper by something like: fetching db entries for extraction, checking
     * for active calls for same "kw" / "location" etc.
     *
     * @return int - delay milliseconds
     */
    public function decide(): int
    {
        return rand(2000, 8000);
    }

}
