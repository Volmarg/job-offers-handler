<?php

namespace JobSearcher\Service\JobSearch\Keyword;

/**
 * Contains keywords handling related logic
 */
class KeywordHandlerService
{

    /**
     * Will glue all keywords into string,
     *
     * @param array       $keywords
     * @param bool        $isEncoded
     * @param string      $separator
     * @param string|null $spacebarReplaceCharacter
     *
     * @return string
     */
    public static function glueAll(array $keywords, bool $isEncoded, string $separator, ?string $spacebarReplaceCharacter = null): string
    {
        $allKeywords = "";
        foreach ($keywords as $index => $keyword) {
            $usedKw = $keyword;
            if (!empty($spacebarReplaceCharacter)) {
                $usedKw = str_replace(" ", $spacebarReplaceCharacter, $keyword);
            }

            $usedKeyword  = ($isEncoded ? urlencode($usedKw) : $usedKw);
            if (0 === $index) {
                $allKeywords .= $usedKeyword;
                continue;
            }

            $allKeywords.= $separator . $usedKeyword;
        }

        return $allKeywords;
    }

}