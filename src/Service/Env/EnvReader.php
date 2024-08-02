<?php

namespace JobSearcher\Service\Env;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Handles reading operations from .env file
 */
class EnvReader extends AbstractController {

    const VAR_DEBUG         = "APP_DEBUG";
    const VAR_APP_ENV       = "APP_ENV";
    const VAR_FETCH_OFFERS_EXTRA_DATA = "FETCH_OFFERS_EXTRA_DATA";
    const APP_ENV_MODE_DEV  = "dev";
    const APP_ENV_MODE_PROD = "prod";

    /**
     * Check if the project runs on the production system
     *
     * @return bool
     */
    public static function isProd(): bool
    {
        return ($_ENV[self::VAR_APP_ENV] === self::APP_ENV_MODE_PROD);
    }

    /**
     * Check if extra data for offers should be fetched or not
     *
     * @return bool
     */
    public static function canFetchOffersExtraData(): bool
    {
        $value = $_ENV[self::VAR_FETCH_OFFERS_EXTRA_DATA] ?? true;
        return ($value === "true" || $value === true);
    }

    /**
     * Check if the project runs on the development system
     *
     * @return bool
     */
    public static function isDev(): bool
    {
        return ($_ENV[self::VAR_APP_ENV] === self::APP_ENV_MODE_DEV);
    }

    /**
     * Returns the current environment in which the app runs in
     *
     * @return string
     */
    public static function getEnvironment(): string
    {
        return $_ENV[self::VAR_APP_ENV];
    }

    /**
     * Check if app is running in debug mode
     *
     * @return bool
     */
    public static function isDebug(): bool
    {
        $isDebug = $_ENV[self::VAR_DEBUG];
        if (is_string($isDebug)) {
            return ($isDebug === "true");
        }

        return $isDebug;
    }

    /**
     * Check if proxy should be enabled or not
     *
     * @return bool
     */
    public static function isProxyEnabled(): bool
    {
        if (!isset($_ENV['IS_PROXY_ENABLED'])) {
            return false;
        }

        return ($_ENV['IS_PROXY_ENABLED'] == 'true' || $_ENV['IS_PROXY_ENABLED'] == 1 || $_ENV['IS_PROXY_ENABLED'] === true);
    }

    /**
     * Check if app is in demo mode
     *
     * @return bool
     */
    public static function isDemo(): bool
    {
        if (!isset($_ENV['IS_DEMO'])) {
            return false;
        }

        return ($_ENV['IS_DEMO'] == 'true' || $_ENV['IS_DEMO'] == 1 || $_ENV['IS_DEMO'] === true);
    }

}
