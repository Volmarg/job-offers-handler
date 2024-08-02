<?php

namespace JobSearcher\Command\Corrections\CompanyData;

use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Repository\Company\CompanyRepository;
use JobSearcher\Service\CompanyData\CompanyEmailService;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Will try to find job application emails for companies,
 * This might be sometimes needed as the rules / logic for checking the company emails will be expanded,
 * and if some companies had no application E-Mails before then that might be an issue for future job offers
 * that will be bound to the company (where the offer itself will have no email)
 *
 * This command name is accurate. Its main goal is to fetch the application
 */
class GetEmailsCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "correction:fetch-company-emails";

    private const OPTION_MODE  = "mode";
    private const OPTION_MONTH = "month";
    private const OPTION_YEAR  = "year";

    // this mode is slower as it needs to crawl the search engines to find for the subpages etc. might be still more accurate
    private const OPTION_MODE_ALL = "all";

    // this mode is faster as it only relies on visiting the company websites (only companies with websites are used)
    private const OPTION_MODE_WITH_WEBSITES_ONLY = "withWebsitesOnly";

    private const ALL_MODES = [
        self::OPTION_MODE_ALL,
        self::OPTION_MODE_WITH_WEBSITES_ONLY,
    ];

    private ?int $month = null;
    private ?int $year = null;
    private string $mode;

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface        $logger
     * @param CompanyRepository      $companyRepository
     * @param CompanyEmailService    $companyEmailService
     * @param OfferExtractionService $offerExtractionService
     */
    public function __construct(
        LoggerInterface                         $logger,
        private readonly CompanyRepository      $companyRepository,
        private readonly CompanyEmailService    $companyEmailService,
        private readonly OfferExtractionService $offerExtractionService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Will attempt to fetch job application emails for companies that don't have any proper email set");
        $this->addOption(self::OPTION_MODE, null, InputOption::VALUE_REQUIRED, "
        Select searching mode: "
        . "- `" . self::OPTION_MODE_ALL                . "`: to search for all companies,"
        . "- `" . self::OPTION_MODE_WITH_WEBSITES_ONLY . "`: to search for companies with websites only");

        $this->addOption(self::OPTION_YEAR, null, InputOption::VALUE_OPTIONAL, "Year for which the companies should be found for");
        $this->addOption(self::OPTION_MONTH, null, InputOption::VALUE_OPTIONAL, "Year for which the companies should be found for");

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->month = $input->getOption(self::OPTION_MONTH);
        $this->year  = $input->getOption(self::OPTION_YEAR);
        $mode        = $input->getOption(self::OPTION_MODE);

        $hasError = false;

        if (
                !is_numeric($this->month)
            &&  !is_null($this->month)
        ) {
            $this->io->error("Month is not numeric value");
            $hasError = true;
        }

        if (
                !is_numeric($this->year)
            &&  !is_null($this->year)
        ) {
            $this->io->error("Year is not numeric value");
            $hasError = true;
        }

        if (!in_array($mode, self::ALL_MODES)) {
            $this->io->error("Incorrect mode `{$mode}`, allowed are: " . json_encode(self::ALL_MODES));
            $hasError = true;
        }

        if ($hasError) {
            die();
        }

        $this->mode = $mode;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
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

        try {
            $this->io->info("Started searching for companies emails for params: ");
            $withWebsitesOnly   = ($this->mode === self::OPTION_MODE_WITH_WEBSITES_ONLY);
            $companiesGenerator = $this->companyRepository->findAllWithoutApplicationEmail($this->year, $this->month, $withWebsitesOnly);

            $this->io->listing([
                "Year: {$this->year}",
                "Month: {$this->month}",
                "Mode: {$this->mode}",
            ]);

            while ($companiesGenerator->valid()) {

                $company = $companiesGenerator->current();
                match ($this->mode) {
                    self::OPTION_MODE_ALL                => $this->companyEmailService->getEmailsForCompany($company),
                    self::OPTION_MODE_WITH_WEBSITES_ONLY => $this->companyEmailService->searchOnCompanyWebsite($company),
                    default                              => throw new Exception("Mode not supported: {$this->mode}"),
                };

                $companiesGenerator->next();
            }

            $this->io->info("Finished searching for companies emails");
            $this->release();
        } catch (Exception|TypeError $e) {
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