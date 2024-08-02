<?php

namespace JobSearcher\Service\TypeProcessor;

/**
 * Handles range based values
 */
class RangeTypeProcessor
{
    private const POSSIBLE_RANGE_CHARACTERS = ["-", ":"];

    /**
     * Will attempt to extract the highest number out of the range,
     * if provided value is not a range, rather some hardcoded value or
     * range defined such as "1000+" then just the number will be returned
     *
     * @param string|null $range
     *
     * @return int|null
     */
    public static function extractHighestValue(?string $range): ?int
    {
        if (empty($range)) {
            return null;
        }

        $rangeArray = self::rangeToArray($range);
        if (!empty($rangeArray)) {
            if (1 === count($rangeArray)) {
                preg_match('#([\d]*)#', $rangeArray[0], $matches);
                $highestValue = $matches[1];
                return (int)trim($highestValue);
            }

            $highestValue = $rangeArray[array_key_last($rangeArray)];
            return (int)trim($highestValue);
        }

        return null;
    }

    /**
     * Will return array of range based values or:
     * - single element array for ranges such as `1000+` or just some hardcoded numbers,
     * - empty array if range transformation could not be done,
     *
     * @param string $range
     *
     * @return array
     */
    private static function rangeToArray(string $range): array
    {
        $rangeArray = [];
        foreach (self::POSSIBLE_RANGE_CHARACTERS as $rangeCharacter) {
            $rangeArray  = explode($rangeCharacter, $range);
            if (!empty($rangeArray)) {
                break;
            }
        }

        return $rangeArray;
    }

}