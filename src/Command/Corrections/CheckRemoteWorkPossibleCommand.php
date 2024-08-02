<?php

namespace JobSearcher\Command\Corrections;

use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Service\JobSearch\Scrapper\BaseScrapperService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Rules for checking if remote work is possible might get expanded over time, so it might be wanted / needed that
 * some re-check is done if maybe the offer allows remote work
 */
class CheckRemoteWorkPossibleCommand extends AbstractCommand
{

    use LockableTrait;

    private const COMMAND_NAME = "correction:check-remote-work-possible";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface           $logger
     * @param JobSearchResultRepository $jobSearchResultRepository
     */
    public function __construct(
        LoggerInterface                   $logger,
        private JobSearchResultRepository $jobSearchResultRepository
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Will check if job offers mention anywhere that remote work is possible");
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock(self::COMMAND_NAME)){
            $output->writeln("This command is already running");
            return self::SUCCESS;
        }

        try{
            $this->io->info("Started checking if offers allow working remotely");
            $handledOffers = $this->jobSearchResultRepository->findBy([
                "remoteJobMentioned" => 0,
            ]);

            $this->io->info("Count of offers to re-check: " . count($handledOffers));

            $foundRemoteWorkPossibleCount = 0;
            foreach ($handledOffers as $offer) {
                $isRemotePossible = BaseScrapperService::scrapMentionedThatRemoteIsPossible(
                    $offer->getJobDescription(),
                    $offer->getJobTitle(),
                    $offer->getLocationsAsStrings(),
                    $offer->getOfferLanguageIsoCodeThreeDigit()
                );

                if ($isRemotePossible) {
                    $foundRemoteWorkPossibleCount++;
                    $offer->setRemoteJobMentioned(true);
                    $this->jobSearchResultRepository->save($offer);
                }
            }

            $this->io->note("{$foundRemoteWorkPossibleCount} marked as possible to work remotely");
            $this->io->info("Finished checking if offers allow working remotely");
            $this->release();
        }catch(Exception | TypeError $e){
            $this->logger->critical("Exception was thrown while calling command", [
                "class" => self::class,
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);

            $this->release();
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}