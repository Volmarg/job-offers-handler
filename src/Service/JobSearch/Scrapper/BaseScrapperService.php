<?php

namespace JobSearcher\Service\JobSearch\Scrapper;

use DataParser\Service\Analyser\TextMatcherService;
use DataParser\Service\Parser\Email\EmailParser;
use DataParser\Service\Parser\Text\TextParser;
use Exception;
use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseMainConfigurationDto;
use JobSearcher\Service\DOM\TagsCleanerService;
use JobSearcher\Service\JobAnalyzer\Constants\RemoteSpellingConstants;
use JobSearcher\Service\JobSearch\Extractor\AbstractExtractor;
use JobSearcher\Service\JobSearch\Scrapper\DomHtml\ScrapperInterface;

/**
 * Base logic for scrapping data from {@see AbstractExtractor}
 */
class BaseScrapperService implements ScrapperInterface
{
    /**
     * Will try to extract email from string, return null if fails
     *
     * @param string $string
     * @return string|null
     */
    public static function extractEmailFromString(string $string): ?string
    {
        $foundEmails = EmailParser::parseEmailsFromString($string);
        if( empty($foundEmails) ){
            return null;
        }

        $firstEmail = $foundEmails[array_key_first($foundEmails)];
        return $firstEmail;
    }

    /**
     * Will check if job offer mentions that remote work is possible
     *
     * @param string      $jobDescription
     * @param string      $jobTitle
     * @param string[]    $locationsNames
     * @param string|null $iso3DigitCode
     *
     * @return bool
     */
    public static function scrapMentionedThatRemoteIsPossible(
        string $jobDescription,
        string $jobTitle,
        array  $locationsNames,
        ?string $iso3DigitCode
    ): bool
    {
        $spellingVariants = RemoteSpellingConstants::getSpellingVariant($iso3DigitCode);

        if(
                TextMatcherService::matchesRemoteWords($spellingVariants, $jobTitle)
            ||  TextMatcherService::matchesRemoteWords($spellingVariants, $jobDescription)
        ) {
            return true;
        }

        foreach ($locationsNames as $locationName) {
            if (empty($locationName)) {
                continue;
            }
            if (TextMatcherService::matchesRemoteWords($spellingVariants, $locationName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Will check if provided string contain information that remote work is possible:
     * - it just checks if given string contains any string from {@see RemoteSpellingConstants} (for target country)
     *   in any form (be it glued, kebab case etc.)
     *
     * @param string                   $checkedString
     * @param BaseMainConfigurationDto $mainConfigDto
     *
     * @return bool
     */
    public static function isRemoteWorkPossible(string $checkedString, BaseMainConfigurationDto $mainConfigDto): bool
    {
        $spellingVariants = RemoteSpellingConstants::getSpellingVariant($mainConfigDto->getSupportedCountry());
        return TextMatcherService::matchesRemoteWords($spellingVariants, $checkedString);
    }

    /**
     * Will remove the whole html content, leaving only what's outside the tags:
     * - can be used when selecting dom elements for getting its content but without all the dom elements that there are
     *
     * @param string $checkedString
     *
     * @return string
     * @throws Exception
     */
    public static function removeHtmContent(string $checkedString): string
    {
        return TagsCleanerService::removeAllHtmlTagsWithContent($checkedString);
    }

    /**
     * Takes the provided string and attempts to extract numbers from it. If it extracts some then returns them,
     * else returns null
     *
     * @param string $string
     *
     * @return int|null
     */
    public static function extractNumbers(string $string): ?int
    {
        return TextParser::extractNumbers($string);
    }

    /**
     * {@see TextParser::getLowerRangeValue()}
     *
     * @param string $targetString
     *
     * @return int|null
     */
    public static function extractLowerRange(string $targetString): ?int
    {
        $result = TextParser::getLowerRangeValue($targetString);
        if (empty($result)) {
            return null;
        }

        return (int) $result;
    }

    /**
     * {@see TextParser::getHigherRangeValue}
     *
     * @param string $targetString
     *
     * @return int|null
     */
    public static function extractHigherRange(string $targetString): ?int
    {
        $result = TextParser::getHigherRangeValue($targetString);
        if (empty($result)) {
            return null;
        }

        return (int) $result;
    }

    /**
     * {@see TextParser::getMinValue()}
     *
     * @param string $targetString
     *
     * @return int|null
     */
    public static function extractMinValue(string $targetString): ?int
    {
        $result = TextParser::getMinValue($targetString);
        if (empty($result)) {
            return null;
        }

        return (int) $result;
    }

    /**
     * {@see TextParser::getMaxValue()}
     *
     * @param string $targetString
     *
     * @return int|null
     */
    public static function extractMaxValue(string $targetString): ?int
    {
        $result = TextParser::getMaxValue($targetString);
        if (empty($result)) {
            return null;
        }

        return (int) $result;
    }

    /**
     * {@see TextParser::obtainDateString}
     *
     * @param string $targetString
     *
     * @return string|null
     */
    public static function obtainDateString(string $targetString): ?string
    {
        $result = TextParser::obtainDateString($targetString);

        return $result;
    }

    /**
     * @param string                   $targetString
     * @param BaseMainConfigurationDto $mainConfigDto
     * @param string                   $splitBy
     * @param string                   $key
     *
     * @return string|null
     */
    public static function explodeAndGetKey(
        string                   $targetString,
        BaseMainConfigurationDto $mainConfigDto,
        string                   $splitBy,
        string                   $key
    ): ?string {
        $parts = explode($splitBy, $targetString);

        return $parts[$key] ?? null;
    }

    /**
     * @param string                   $targetString
     * @param BaseMainConfigurationDto $mainConfigDto
     * @param string                   $key
     *
     * @return bool
     */
    public static function strContains(
        string                   $targetString,
        BaseMainConfigurationDto $mainConfigDto,
        string                   $key
    ): bool {
        return str_contains($targetString, $key);
    }

    /**
     * @param string                   $targetString
     * @param BaseMainConfigurationDto $mainConfigDto
     * @param string                   $key
     *
     * @return bool
     */
    public static function strNotContains(
        string                   $targetString,
        BaseMainConfigurationDto $mainConfigDto,
        string                   $key
    ): bool {
        return !str_contains($targetString, $key);
    }

    /**
     * Takes string such as "te | st | company" and extracts the "company" part after last pipe in string
     *
     * @param string $targetString
     *
     * @return string|null
     */
    public static function getStringAfterLastPipeCharacter(string $targetString): ?string
    {
        $exploded    = explode("|", $targetString);
        $lastElement = trim(array_pop($exploded));

        return $lastElement;
    }

    /**
     * Takes string such as "te | st | company" and extracts the "st" part before last pipe in string
     *
     * @param string $targetString
     *
     * @return string|null
     */
    public static function getStringBeforeLastPipeCharacter(string $targetString): ?string
    {
        $exploded = explode("|", $targetString);
        array_pop($exploded);

        $beforeLastElement = trim(array_pop($exploded) ?? '');

        return $beforeLastElement;
    }
}