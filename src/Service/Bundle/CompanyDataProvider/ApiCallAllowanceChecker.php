<?php

namespace JobSearcher\Service\Bundle\CompanyDataProvider;

use CompanyDataProvider\Service\AllowanceChecker\AllowanceCheckerInterface;
use Exception;
use JobSearcher\Repository\Service\ServiceStateRepository;
use Psr\Log\LoggerInterface;

/**
 * Check if api can be called for the CompanyDataProvider bundle
 */
class ApiCallAllowanceChecker implements AllowanceCheckerInterface
{

    public function __construct(
        private readonly ServiceStateRepository $serviceStateRepository,
        private readonly LoggerInterface        $logger
    ) {

    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function isAllowed(mixed $checkedData): bool
    {
        if (!is_string($checkedData)) {
            throw new Exception(__CLASS__ . "::" . __METHOD__ . " expects string, got: " . gettype($checkedData));
        }

        /**
         * Need to check if it's migration call, else it shows, a bunch of exception that tables do not exist in DB etc.
         * $argv etc. are empty here. It matters not if true or false gets returned for this case.
         * Would be nice to have this part of code as service tho... or find a better solution
         */
        $argv = $GLOBALS['args'][0]['argv'] ?? [];
        if (!empty($argv)) {
            foreach ($argv as $arg) {
                if (str_contains($arg, "doctrine:migrations:migrate")) {
                    return true;
                }
            }
        }

        $serviceState = $this->serviceStateRepository->findByName($checkedData);
        if (empty($serviceState)) {

            $message = "
                Tried to check api call allowance for service: {$checkedData}, 
                yet no such service is present in database.
                This is an emergency because this logic is suppose to SHUTDOWN the connection to api,
                to prevent generating large costs, and if it's not working then YOU HAVE AN ISSUE!
                
                Thus returning FALSE.
            ";
            $message = preg_replace("#[ ]{2,}#", " ", $message);
            $this->logger->emergency($message);

            return false;
        }

        return $serviceState->isActive();
    }
}