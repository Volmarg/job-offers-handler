<?php

namespace JobSearcher\Service\TypeProcessor;

use Exception;
use LogicException;

/**
 * Handles array related logic
 */
class ArrayTypeProcessor
{

    /**
     * Takes string in form:
     * - "word.word2.word3"
     *
     * Turns it into
     * - ["word", "word2", "word3"]
     * @param string $parsedString
     * @return array
     */
    public static function dotSeparatedStringToArrayOfKeys(string $parsedString): array
    {
        $arrayOfKeys = explode(".",$parsedString);
        return $arrayOfKeys;
    }

    /**
     * Takes an array and searches for value in it by using dot separated string such as:
     * - "word1.word2.word3"
     *
     * @param array $arrayToSearchInto
     * @param string|null $parsedString
     * @return mixed
     */
    public static function getDataFromArrayByDotSeparatedString(array $arrayToSearchInto, ?string $parsedString): mixed
    {
        $arrayOfKeys    = self::dotSeparatedStringToArrayOfKeys($parsedString);
        $traversedValue = $arrayToSearchInto;
        $countOfKeys    = count($arrayOfKeys) - 1;

        foreach($arrayOfKeys as $index => $key){

            $traversedValue = $traversedValue[$key] ?? null;
            if(
                    !is_array($traversedValue)
                ||  $index === $countOfKeys
            ){
                return $traversedValue;
            }

        }

        return null;
    }

    /**
     * Will check if array key is set, if not then will throw exception
     *
     * @param array $testedArray
     * @param string $stringToSearchWith
     * @throws Exception
     */
    public static function checkArrayKeyIsSetByDottedString(array $testedArray, string $stringToSearchWith): void
    {
        $arrayOfSearchedKeys = self::dotSeparatedStringToArrayOfKeys($stringToSearchWith);
        self::checkArrayKeyIsSet($testedArray, $arrayOfSearchedKeys);
    }

    /**
     * Will check if array key is set, if not then will throw exception
     *
     * @param array $testedArray
     * @param array $keysToCheck
     * @throws Exception
     */
    public static function checkArrayKeyIsSet(array $testedArray, array $keysToCheck): void
    {
        $checkedArray = $testedArray;
        foreach($keysToCheck as $key){
            if(
                    is_array($checkedArray)
                &&  array_key_exists($key, $checkedArray)
            ){
                $checkedArray = $checkedArray[$key];
            }elseif(
                    is_array($checkedArray)
                &&  !array_key_exists($key, $checkedArray)
            ){
                self::throwMissingStructureException($keysToCheck);
            }
        }

    }

    /**
     * Will check if given key is the first one in array
     *
     * @param string $searchedKey
     * @param array  $testedArray
     *
     * @return bool
     */
    public static function isFirstKeyOfArray(string $searchedKey, array $testedArray): bool
    {
        return ($searchedKey == array_key_first($testedArray) );
    }

    /**
     * Will search for closest key for numeric value in array
     *
     * @param int   $searchedValue
     * @param array $targetArray
     *
     * @return int | null
     */
    public static function getKeyForClosestNumber(int $searchedValue, array $targetArray): int | null
    {
        foreach ($targetArray as $valueInArray) {
            if (!is_numeric($valueInArray)) {
                throw new LogicException("At least one value in array is not numeric. Not allowed! Array: " . json_encode($targetArray));
            }
        }

        $closestLowestKey = null;
        foreach ($targetArray as $key => $value) {
            if ($value < $searchedValue) {
                $closestLowestKey = $key;
                continue;
            }

            if ($value >= $searchedValue) {
                return $key;
            }
        }

        return $closestLowestKey;
    }

    /**
     * Handles throwing exception about missing configuration file keys
     *
     * @throws Exception
     */
    private static function throwMissingStructureException(array $arrayOfKeys): void
    {
        $json = json_encode($arrayOfKeys, JSON_PRETTY_PRINT);
        throw new Exception("Missing given structure in the array: {$json}");
    }

}