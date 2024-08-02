<?php

namespace JobSearcher\Service\DOM;

/**
 * Handles wrapping all kind of data into the html tags which can be lager used on front
 */
class DomTagWrapperService implements DomTagWrapperInterface
{

    /**
     * Will wrap content inside element with class:
     * - {@see DomTagWrapperService::FRONT_CLASS_TAG_JOB_OFFER_INCLUDED}
     *
     * @param string $contentInsideTag
     * @return string
     */
    public static function wrapIntoClassTagJobOfferIncluded(string $contentInsideTag): string
    {
        return self::wrapContentIntoClass($contentInsideTag, self::FRONT_CLASS_TAG_JOB_OFFER_INCLUDED);
    }

    /**
     * Will wrap content inside element with class:
     * - {@see DomTagWrapperService::FRONT_CLASS_TAG_JOB_OFFER_INCLUDED_MANDATORY}
     *
     * @param string $contentInsideTag
     * @return string
     */
    public static function wrapIntoClassTagJobOfferIncludedMandatory(string $contentInsideTag): string
    {
        return self::wrapContentIntoClass($contentInsideTag, self::FRONT_CLASS_TAG_JOB_OFFER_INCLUDED_MANDATORY);
    }

    /**
     * Will wrap content inside element with class:
     * - {@see DomTagWrapperService::FRONT_CLASS_TAG_JOB_OFFER_EXCLUDED}
     *
     * @param string $contentInsideTag
     * @return string
     */
    public static function wrapIntoClassTagJobOfferExcluded(string $contentInsideTag): string
    {
        return self::wrapContentIntoClass($contentInsideTag, self::FRONT_CLASS_TAG_JOB_OFFER_EXCLUDED);
    }

    /**
     * Will wrap the tags into color classes so that all of them can later on be highlighted on front
     *
     * @param string $content
     * @param array  $colorWithKeywords
     *
     * @return string
     */
    public static function wrapTagsIntoColorClass(string $content, array $colorWithKeywords): string
    {
        $replacedContent = $content;
        foreach ($colorWithKeywords as $color => $tags) {
            foreach ($tags as $tag) {
                $className       = self::buildKeywordHighlightColorClass($color);
                $wrappedKeyword  = self::wrapContentIntoClass($tag, $className);
                $replacedContent = str_ireplace($tag, $wrappedKeyword, $replacedContent);
            }
        }

        return $replacedContent;
    }

    /**
     * Will wrap string content into dom element with tag that consist given class
     *
     * @param string $content
     * @param string $className
     * @return string
     */
    private static function wrapContentIntoClass(string $content, string $className): string
    {
        return "<span class='{$className}'>{$content}</span>";
    }

    /**
     * Wrap the keyword into class name that consist of color name.
     * The color name is later on used on frontend to highlight the given keywords.
     *
     * @param string $colorName
     *
     * @return string
     */
    private static function buildKeywordHighlightColorClass(string $colorName): string
    {
        return "highlighted-keyword-color-" . $colorName;
    }

}