<?php

namespace JobSearcher\QueryBuilder\Modifier\Single;

use JobSearcher\Exception\QueryBuilder\DataNotFoundException;

/**
 * Provides some base common logic for all the single modifiers
 */
class BaseSingleModifier
{
    /**
     * @var array $DATA
     */
    private static array $DATA = [];

    /**
     * Will return data that can be used for modifications
     *
     * @return array
     */
    public static function getData(): array
    {
        return self::$DATA;
    }

    /**
     * Check if there is any data stored under given key
     *
     * @param string $key
     *
     * @return bool
     */
    public static function hasData(string $key): bool
    {
        try {
            self::getDataForKey($key);

            return true;
        } catch (DataNotFoundException) {
            return false;
        }
    }

    /**
     * Will return the data stored under the key,
     * Throws exception if data is not found, since null can be also valid type of returned data
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws DataNotFoundException
     */
    public static function getDataForKey(string $key): mixed
    {
        $data = self::getData();
        if (!array_key_exists($key, $data)) {
            throw new DataNotFoundException($key);
        }

        return $data[$key];
    }

    /**
     * Add data key to the data array
     *
     * @param string $key
     * @param mixed  $data
     *
     * @return void
     */
    public static function addDataKey(string $key, mixed $data): void
    {
        self::$DATA[$key] = $data;
    }
}