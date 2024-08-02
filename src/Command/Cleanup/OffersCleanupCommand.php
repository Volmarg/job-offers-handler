<?php

namespace JobSearcher\Command\Cleanup;

use Doctrine\ORM\EntityManagerInterface;
use JobSearcher\Command\AbstractCommand;
use DateTime;
use Exception;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Repository\Extraction\JobOfferExtractionRepository;
use JobSearcher\Repository\JobSearchResult\JobSearchResultRepository;
use JobSearcher\Service\Extraction\Offer\OfferExtractionCleanupService;
use JobSearcher\Service\Extraction\Offer\OfferExtractionService;
use JobSearcher\Service\JobSearch\Result\SearchResultCleanupService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use TypeError;

/**
 * Handles removing the old offers
 */
class OffersCleanupCommand extends AbstractCommand
{
    use LockableTrait;

    public const COMMAND_NAME = "cleanup:offers";

    private int $countRemovedExtractions = 0;
    private int $countRemovedOffers = 0;

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    private readonly int $maxDaysExtractionWithOffersLifetime;

    /**
     * @param LoggerInterface               $logger
     * @param JobOfferExtractionRepository  $jobOfferExtractionRepository
     * @param ParameterBagInterface         $parameterBag
     * @param EntityManagerInterface        $entityManager
     * @param SearchResultCleanupService    $searchResultCleanupService
     * @param OfferExtractionCleanupService $offerExtractionCleanupService
     * @param OfferExtractionService        $offerExtractionService
     * @param JobSearchResultRepository     $jobSearchResultRepository
     */
    public function __construct(
        LoggerInterface                                $logger,
        private readonly JobOfferExtractionRepository  $jobOfferExtractionRepository,
        private readonly ParameterBagInterface         $parameterBag,
        private readonly EntityManagerInterface        $entityManager,
        private readonly SearchResultCleanupService    $searchResultCleanupService,
        private readonly OfferExtractionCleanupService $offerExtractionCleanupService,
        private readonly OfferExtractionService        $offerExtractionService,
        private readonly JobSearchResultRepository     $jobSearchResultRepository
    )
    {
        $this->maxDaysExtractionWithOffersLifetime = $this->parameterBag->get("max_days_extraction_with_offers_lifetime");
        $this->logger                              = $logger;
        parent::__construct($this->logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Handles removal of extractions and related job offers");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
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
            $this->entityManager->beginTransaction();

            $this->io->info("Started removing extractions and related offers");

            $maxDate     = (new DateTime())->modify("-{$this->maxDaysExtractionWithOffersLifetime} DAYS");
            $extractions = $this->jobOfferExtractionRepository->findOlderThan($maxDate);

            $this->removeOffersForExtractions($extractions);
            $this->entityManager->flush();
            $this->entityManager->commit();
            $this->release();

            $this->io->note("Finished removing extractions with related offers");
            $this->io->listing([
                "Removed extractions : {$this->countRemovedExtractions}",
                "Removed offers : {$this->countRemovedOffers}",
            ]);
        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
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

    /**
     * @param JobOfferExtraction[] $extractions
     */
    private function removeOffersForExtractions(array $extractions): void
    {
        foreach ($extractions as $extraction) {
            if (!$this->offerExtractionCleanupService->canExtractionBeRemoved($extraction)) {
                $this->io->warning("Tried removing non-removable extraction of id: {$extraction->getId()}");
                continue;
            }

            foreach ($extraction->getJobSearchResults() as $offer) {
                if (!$this->searchResultCleanupService->canOfferBeRemoved($offer)) {
                    continue;
                }
                $this->removeOffer($offer);
            }

            if (!$extraction->getJobSearchResults()->isEmpty()) {
                continue;
            }

            $this->removeExtraction($extraction);
        }
    }

    /**
     * @param JobOfferExtraction $extraction
     */
    private function removeExtraction(JobOfferExtraction $extraction): void
    {
        $this->io->info("Removing extraction with id: {$extraction->getId()}");

        try {
            $this->entityManager->remove($extraction);
            $this->countRemovedExtractions++;
        } catch (Exception|TypeError $e) {
            $this->logger->warning("Could not remove extraction with id: {$extraction->getId()}", [
                "exception" => [
                    "msg"   => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                    "class" => $e::class,
                ]
            ]);
        }
    }

    /**
     * @param JobSearchResult $offer
     *
     * @return void
     */
    private function removeOffer(JobSearchResult $offer): void
    {
        try {
            $this->io->note("Removing offer: {$offer->getId()}, offer class: " . $offer::class);

            foreach($offer->getExtractionKeyword() as $test){
                $this->entityManager->remove($test);
            }
            $this->entityManager->remove($offer);
            $this->countRemovedOffers++;
        } catch (Exception|TypeError $e) {
            $this->logger->warning("Could not remove offer with id: {$offer->getId()}", [
                "exception" => [
                    "msg"   => $e->getMessage(),
                    "trace" => $e->getTraceAsString(),
                    "class" => $e::class,
                ]
            ]);
        }
    }

}