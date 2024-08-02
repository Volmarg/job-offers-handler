<?php

namespace JobSearcher\Constants\JobOfferService\Selectors\Norway;

/**
 * This class was created for better readability as the jobbsafari collects offers from other services,
 * so it's needed to build one big selector with possible selectors for all services,
 */
class JobbSafariNoSelectors
{
    private const FINN_NO_DESCRIPTION = ".import-decoration";
    private const ARBEIDSPLASSEN_DESCRIPTION = '[as="article"] div > .ad-html-content';
    private const KARRIERESTART_DESCRIPTION = '.cp-lft-new .jobad_about';
    private const JOBBSAFARI_DESCRIPTION = '.jobtext-jobad__body';

    public const GROUPED_DESCRIPTION_SELECTOR =
        self::FINN_NO_DESCRIPTION
        . ","
        . self::ARBEIDSPLASSEN_DESCRIPTION
        . ","
        . self::KARRIERESTART_DESCRIPTION
        . ","
        . self::JOBBSAFARI_DESCRIPTION
    ;
}