<?php

namespace JobSearcher\Service\Bundle\ProxyProvider;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JobSearcher\Command\JobSearch\AllJobOffersExtractorCommand;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Exception\Bundle\ProxyProvider\ExternalProxyNotReachableException;
use JobSearcher\Service\Env\EnvReader;
use ProxyProviderBridge\Request\IsProxyReachableRequest;
use ProxyProviderBridge\Service\BridgeService;
use TypeError;

/**
 * In general the proxy provider is not really used inside current project, however the bundles in the project DO use
 * it on different steps.
 *
 * There is a need to check if external proxy is reachable, and if not then the {@see AllJobOffersExtractorCommand}
 * must be stopped and the founds must be returned to user (depending on the progress {@see JobOfferExtraction::$percentageDone}).
 *
 * Instead of doing checks in all the places when the bundles are using the {@see BridgeService},
 * there will be one check in THIS project, since all the proxy based bundles are in general used only in CURRENT project anyway.
 */
class ProxyProviderService
{
    public const PROXY_REACHABILITY_CHECK_INTERVAL_MIN = 2;

    /**
     * Describes when the next check for proxy reachability should be made
     *
     * @var DateTime
     */
    public static DateTime $PROXY_REACHABILITY_NEXT_CHECK;

    /**
     * @param BridgeService $bridgeService
     */
    public function __construct(
        private readonly BridgeService $bridgeService,
    ) {
        self::setProxyReachabilityNextCheck();
    }

    /**
     * Checks if external proxy is reachable.
     * If it is, nothing happens, else exception is thrown.
     *
     * The call (check) is made only when current date is >={@see self::$PROXY_REACHABILITY_NEXT_CHECK}
     * Each time the check is made the: {@see self::setProxyReachabilityNextCheck()} is called.
     *
     * @throws ExternalProxyNotReachableException
     * @throws GuzzleException
     */
    public function checkProxyReachability(): void
    {
        if (!EnvReader::isProxyEnabled()) {
            return;
        }

        if ((new DateTime())->getTimestamp() <= self::$PROXY_REACHABILITY_NEXT_CHECK->getTimestamp()) {
            return;
        }

        $this->isExternalProxyReachable();
        self::setProxyReachabilityNextCheck();
    }

    /**
     * Check if the target system is disabled
     *
     * @throws ExternalProxyNotReachableException
     * @throws GuzzleException
     */
    private function isExternalProxyReachable(): void
    {
        try {
            $request  = new IsProxyReachableRequest();
            $response = $this->bridgeService->isExternalProxyReachable($request);
            if (!$response->isSuccess()) {
                throw new ExternalProxyNotReachableException("External proxy services on Proxy Provider are not reachable!. Msg: " . $response->getMessage());
            }
        } catch (Exception|TypeError $e) {
            $original = " | Msg: {$e->getMessage()}, trace: {$e->getTraceAsString()}";
            throw new ExternalProxyNotReachableException("Exception while checking if external proxy service is reachable!. " . $original);
        }
    }

    /**
     * Will increment the {@see self::$PROXY_REACHABILITY_NEXT_CHECK}.
     */
    private static function setProxyReachabilityNextCheck(): void
    {
        if (!isset(self::$PROXY_REACHABILITY_NEXT_CHECK)) {
            self::$PROXY_REACHABILITY_NEXT_CHECK = new DateTime();
        }

        self::$PROXY_REACHABILITY_NEXT_CHECK->modify("+" . self::PROXY_REACHABILITY_CHECK_INTERVAL_MIN . " MINUTE");
    }

}