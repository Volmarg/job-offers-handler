<?php
namespace JobSearcher\Service\TypeProcessor;

use DateTime as DateTimeObject;

/**
 * Handles processing of the {@see DateTimeProcessor}
 */
class DateTimeProcessor
{
    /**
     * Example output: 2022-02-02 13:13:54
     */
    public const FORMAT_Y_M_D_H_I_S = 'Y-m-d H:i:s';

    /**
     * Will return current DateTime wrapped between 2 characters,
     * Returns the current DateTime as string in desired format (example {@see DateTimeProcessor::FORMAT_Y_M_D_H_I_S})
     *
     * @param string $openCharacter
     * @param string $closeCharacter
     * @param string $format
     *
     * @return string
     */
    public static function nowAsStringWrappedBetweenCharacters(string $openCharacter, string $closeCharacter, string $format = self:: FORMAT_Y_M_D_H_I_S): string
    {
        return $openCharacter . (new DateTimeObject())->format($format) ."{$closeCharacter} ";
    }

}