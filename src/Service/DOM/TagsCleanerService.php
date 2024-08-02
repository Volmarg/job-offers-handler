<?php

namespace JobSearcher\Service\DOM;

use Exception;
use TypeError;

/**
 * Handles cleaning the html tags
 */
class TagsCleanerService
{
    /**
     * Key is either tag name or some simple regexp which matches given tag explicitly (be it escaping some special characters in its name)
     */
    private const DEFAULT_REMOVED_TAGS = [
        "script"    => true,
        "style"     => true,
        "head"      => true,
        "iframe"    => true,
        "body"      => false,
        "html"      => false,
        "\!DOCTYPE" => true,
    ];

    /**
     * Will remove the provided tags completely - alongside with their inner content (if there is such)
     *
     * @param array  $tagsMatches - is either tag name or some simple regexp which matches given tag explicitly
     *                              (be it escaping some special characters in its name)
     * @param string|null $content
     *
     * @return string
     */
    public static function removeTags(?string $content, array $tagsMatches = self::DEFAULT_REMOVED_TAGS): string
    {
        if (is_null($content)) {
            return "";
        }

        $cleanedContent = $content;
        foreach ($tagsMatches as $tagMatch => $removeInnerContent) {
            $regexp = self::buildTagMatchRegexp($tagMatch);
            if ($removeInnerContent) {
                $cleanedContent = preg_replace($regexp, "", $cleanedContent);
                continue;
            }

            preg_match($regexp, $cleanedContent, $matches);
            $innerContent = $matches['INNER_CONTENT'] ?? null;
            if (empty($innerContent)) {
                continue;
            }

            $cleanedContent = preg_replace($regexp, $innerContent, $cleanedContent);
        }

        return $cleanedContent;
    }

    /**
     * Will remove all the html tags alongside with their content, will however leave anything outside the tags
     * so if there is some text and some tags after that text, the tags are wiped, but the text itself stays and
     * is returned,
     *
     * Works for:
     * ```
     * "Text"
     * <div>test</div>
     * ```
     *
     * Will not work for:
     * ```
     * "Text"
     * <div>stuff</div>
     * "Test" <p></p>
     * ```
     *
     * Will return just the first "Text" Node. That's due to the regexp not being perfect
     *
     * @param string $string
     * @return string
     * @throws Exception
     */
    public static function removeAllHtmlTagsWithContent(string $string): string
    {
        // that's dirty but otherwise got preg backtrace error, this high value doesn't seem to affect the performance
        ini_set('pcre.backtrack_limit', 100000000);
        $regexp = "#<.*>.*<\/.*>#sm";

        try {
            $modifiedString = trim(preg_replace($regexp, "", $string));
            if (!empty($modifiedString)) {
                return trim($modifiedString);
            }
        }catch(Exception | TypeError $e){
            $regexpLastError = preg_last_error_msg();
            $message = "
                - Original message: {$e->getMessage()},
                - Regexp last error: {$regexpLastError}
                - Trace: {$e->getTraceAsString()}
            ";

            throw new Exception($message);
        }

        return $modifiedString;
    }

    /**
     * Will build regexp that catches the tag alongside with its inner content (works also on self-closing tags)
     *
     * @param string $tagName
     *
     * @return string
     */
    private static function buildTagMatchRegexp(string $tagName): string
    {
        return "#<[ ]*{$tagName}[\D]*>(?<INNER_CONTENT>(.*))<\/{$tagName}>|<{$tagName}.*[\/]?>#Umsi";
    }
}