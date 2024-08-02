<?php

namespace JobSearcher\Service\Shell\Command;

use Exception;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Already tried using the:
 * - {@see Process} -> {@link https://symfony.com/doc/current/components/process.html}: didnt even started
 *
 * Thus, the old-school call to {@see exec()}
 */
class ShellCommandService
{
    public function __construct(
        private readonly LoggerInterface $shellLogger
    ){}

    /**
     * Will execute the command, and will log it's output
     *
     * Return information if call is finished with success
     *
     * @param string      $calledCommand
     * @param string|null $chdir
     *
     * @return bool
     *
     * @throws Exception
     */
    public function executeWithLoggedOutput(string $calledCommand, ?string $chdir = null, string $nonZeroResponseLogLevel = Logger::CRITICAL): bool
    {
        $oldDir = getcwd();
        if (!empty($chdir)) {
            if (!is_dir($chdir)) {
                throw new Exception("Target dir for chdir() does not exist. Tried to swap to: {$chdir}");
            }
            chdir($chdir);
        }

        exec($calledCommand . "  2>&1", $outputLines, $outputCode);

        if (!empty($chdir)) {
            chdir($oldDir);
        }

        $isSuccessfulExecution = ($outputCode == 0);

        $logBag = [
            "Called command" => $calledCommand,
            "command"        => $calledCommand,
            "output"         => $outputLines,
            "code"           => $outputCode,
            "success"        => $isSuccessfulExecution,
        ];

        if ($isSuccessfulExecution) {
            $this->shellLogger->debug("Shell command output", $logBag);
        }else{
            $this->shellLogger->log($nonZeroResponseLogLevel, "Shell command returned non zero response!", $logBag);
        }

        return $isSuccessfulExecution;
    }
}