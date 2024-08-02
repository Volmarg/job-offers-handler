<?php

namespace JobSearcher\Service\TypeProcessor;

use DataParser\Service\Parser\Text\TextParser;

/**
 * Stores logic for handling string in different ways
 */
class StringTypeProcessor
{

    /**
     * Expects camelCased string as input, as result returns string being a text created from the camel cased words,
     * each word is separated with space-bar character
     *
     * @param string $camelCasedString
     *
     * @return string
     */
    public static function camelCaseIntoWords(string $camelCasedString): string
    {
        $wordsArray   = preg_split('/(?=[A-Z])/',$camelCasedString);
        $string       = implode( " ", $wordsArray);
        $prettyString = ucfirst(strtolower($string));

        return $prettyString;
    }

    /**
     * Works just like {@see \substr()} but it will check if there is any "not closed" html tag after processing,
     * if there is any, then the substring will return longer text up to the point where the html tag is getting closed
     *
     * Taken from:
     * - {@link https://stackoverflow.com/questions/8933491/php-substr-but-keep-html-tags}
     *
     * @param string $string
     * @param int    $maxLength
     *
     * @return string
     */
    public static function substrAndKeepHtmlTag(string $string, int $maxLength): string
    {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        $html = substr($string, 0, $maxLength);
        preg_match_all("#<([a-zA-Z]+)#", $html, $result);

        foreach ($result[1] AS $key => $value) {
            if (strtolower($value) == 'br') {
                unset($result[1][$key]);
            }
        }
        $openedTags = $result[1];

        preg_match_all("#</([a-zA-Z]+)>#iU", $html, $result);
        $closedTags = $result[1];

        foreach ($closedTags AS $key => $value) {
            if (($k = array_search($value, $openedTags)) === false) {
                continue;
            } else {
                unset($openedTags[$k]);
            }
        }

        if (empty($openedTags)) {
            if (strpos($string, ' ', $maxLength) == $maxLength) {
                return $html . "...";
            } else {
                return substr($string, 0, strpos($string, ' ', $maxLength)) . "...";
            }
        }

        $position = 0;
        $closeTag = '';
        foreach ($openedTags AS $key => $value) {
            $p = strpos($string, ('</' . $value . '>'), $maxLength);

            if ($p === false) {
                $string .= ('</' . $value . '>');
            } elseif ($p > $position) {
                $closeTag = '</' . $value . '>';
                $position = $p;
            }
        }

        if ($position == 0) {
            return $string;
        }

        return substr($string, 0, $position) . $closeTag;
    }

    /**
     * Does not only normal trim, but also removes the spacebar represented by html entity, removes new lines etc.
     *
     * @param string|null $targetString
     *
     * @return string|null
     */
    public static function trim(?string $targetString): ?string
    {
        if (is_null($targetString)) {
            return null;
        }

        return TextParser::deepTrim($targetString);
    }
}