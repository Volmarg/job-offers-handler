<?php

namespace JobSearcher\Service\JobSearch\Scrapper\DomHtml;

use JobSearcher\DTO\JobService\SearchConfiguration\Base\BaseMainConfigurationDto;
use JobSearcher\Service\JobService\ConfigurationBuilder\DomHtml\DomHtmlConfigurationBuilderInterface;

/**
 * Defines common logic or consist of data to prevent from bloating
 * Each of these methods accept given params (in given order):
 * - processed string (extracted by using selector config),
 * - {@see BaseMainConfigurationDto}
 * - ...[args passed from {@see DomHtmlConfigurationBuilderInterface::KEY_CALLED_METHOD_ARGS], example: {@see ScrapperService::explodeAndGetKey()}
 */
interface ScrapperInterface
{
    /** @see ScrapperService::extractEmailFromString()  */
    public const METHOD_EXTRACT_EMAIL_FROM_STRING = "extractEmailFromString";

    /** @see ScrapperService::getStringAfterLastPipeCharacter()  */
    public const METHOD_GET_STRING_AFTER_LAST_PIPE_CHARACTER = "getStringAfterLastPipeCharacter";

    /** @see ScrapperService::getStringBeforeLastPipeCharacter()  */
    public const METHOD_GET_STRING_BEFORE_LAST_PIPE_CHARACTER = "getStringBeforeLastPipeCharacter";

    /** @see ScrapperService::isRemoteWorkPossible() */
    public const METHOD_IS_REMOTE_WORK_POSSIBLE = "isRemoteWorkPossible";

    /** @see ScrapperService::removeHtmContent() */
    public const METHOD_REMOVE_HTML_CONTENT = "removeHtmContent";

    /** @see ScrapperService::extractNumbers() */
    public const EXTRACT_NUMBERS = "extractNumbers";

    /** @see ScrapperService::extractLowerRange() */
    public const EXTRACT_LOWER_RANGE = "extractLowerRange";

    /** @see ScrapperService::extractHigherRange() */
    public const EXTRACT_HIGHER_RANGE = "extractHigherRange";

    /** @see ScrapperService::extractMinValue() */
    public const EXTRACT_MIN_VALUE = "extractMinValue";

    /** @see ScrapperService::extractMaxValue() */
    public const EXTRACT_MAX_VALUE = "extractMaxValue";

    /** @see ScrapperService::obtainDateString() */
    public const EXTRACT_DATE_FROM_STRING = "obtainDateString";

    /** @see ScrapperService::explodeAndGetKey() */
    public const EXPLODE_AND_GET_KEY = "explodeAndGetKey";

    /** @see ScrapperService::strContains() */
    public const STR_CONTAINS = "strContains";

    /** @see ScrapperService::strNotContains() */
    public const STR_NOT_CONTAINS = "strNotContains";

    public const ALLOWED_METHODS = [
        self::METHOD_GET_STRING_AFTER_LAST_PIPE_CHARACTER,
        self::METHOD_GET_STRING_BEFORE_LAST_PIPE_CHARACTER,
        self::METHOD_EXTRACT_EMAIL_FROM_STRING,
        self::METHOD_IS_REMOTE_WORK_POSSIBLE,
        self::METHOD_REMOVE_HTML_CONTENT,
        self::EXTRACT_NUMBERS,
        self::EXTRACT_LOWER_RANGE,
        self::EXTRACT_HIGHER_RANGE,
        self::EXTRACT_MIN_VALUE,
        self::EXTRACT_MAX_VALUE,
        self::EXTRACT_DATE_FROM_STRING,
        self::EXPLODE_AND_GET_KEY,
        self::STR_CONTAINS,
        self::STR_NOT_CONTAINS,
    ];

}