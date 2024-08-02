<?php

namespace JobSearcher\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Common logic for all the command
 */
abstract class AbstractCommand extends Command
{
    protected const MSG_EXTRACTION_IS_RUNNING = "Cannot start this command. Reason: offer extraction command is currently running";
    public const PREFIX_NAMESPACE = "job-searcher";

    /**
     * Will return command name
     * @return string
     */
    abstract protected function getCommandName() : string;

    /**
     * @var SymfonyStyle $io
     */
    protected SymfonyStyle $io;

    /**
     * @var LoggerInterface $logger
     */
    protected LoggerInterface $logger;

    /**
     * @var float $executionStartTime
     */
    private float $executionStartTime;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    )
    {
        parent::__construct(self::PREFIX_NAMESPACE . ":" . $this->getCommandName());
        $this->logger             = $logger;
        $this->executionStartTime = microtime(true);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Calling the destruct for each existing command in order to provide some information for each call - upon finishing the call
     */
    public function __destruct()
    {
        $endTime        = microtime(true);
        $runTimeSeconds = round($endTime - $this->executionStartTime, 2);
        $runTimeMinutes = round($runTimeSeconds / 60, 2);

        $highestMemoryUsedMb      = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $highestMemoryAllocatedMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        if (isset($this->io)) {
            $this->io->note("\n Script was running: {$runTimeMinutes} minute.s ({$runTimeSeconds} second/s) \n");
            $this->io->note("\n Highest memory usage recorded was: {$highestMemoryUsedMb} MB \n");
            $this->io->note("\n Highest memory allocation recorded was: {$highestMemoryAllocatedMb} MB \n");
        }
    }

}