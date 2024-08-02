<?php

namespace JobSearcher\Command\Corrections\CompanyData;

use DateTime;
use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Service\CompanyData\CompanyDataService;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Handles fetching company data
 */
class CompanyDataCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "correction:fetch-company-data";
    private const LAST_JOB_APPLICATION_EMAIL_CHECK_DAYS_OFFSET = 10;

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface        $logger
     * @param CompanyDataService     $companyDataService
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        LoggerInterface $logger,
        private readonly CompanyDataService $companyDataService,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Will handle fetching company data. Afterwards it inserts the the found data in DB");
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->offerExtractionService->isAnyExtractionRunning()) {
            $this->io->warning(self::MSG_EXTRACTION_IS_RUNNING);
            return self::SUCCESS;
        }

        if (!$this->lock(self::COMMAND_NAME)){
            $output->writeln("This command is already running");
            return self::SUCCESS;
        }

        try{
            $this->io->info("Started fetching company data");
            $minLastDataSearchRun = (new DateTime())->modify("-" . self::LAST_JOB_APPLICATION_EMAIL_CHECK_DAYS_OFFSET . " days");

            $this->companyDataService->getCompaniesData($minLastDataSearchRun);

            $this->io->info("Finished fetching company data");
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