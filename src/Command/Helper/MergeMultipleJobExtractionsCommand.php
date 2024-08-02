<?php

namespace JobSearcher\Command\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Entity\Extraction\ExtractionKeyword2Offer;
use JobSearcher\Entity\Extraction\JobOfferExtraction;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Service\Env\EnvReader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Handles merging multiple job extractions into one
 */
class MergeMultipleJobExtractionsCommand extends AbstractCommand
{
    use LockableTrait;

    private const COMMAND_NAME         = "helper:merge-multiple-job-extractions";
    private const PARAM_EXTRACTION_IDS = "extraction-ids";

    /**
     * @var array $mergedExtractionIds
     */
    private array $mergedExtractionIds = [];

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct($logger);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $extractionIdsString       = $input->getOption(self::PARAM_EXTRACTION_IDS);;
        $this->mergedExtractionIds = explode(",", $extractionIdsString);
        if (empty($this->mergedExtractionIds)) {
            throw new Exception("Expected parameter " . self::PARAM_EXTRACTION_IDS . " to be an array, got type: " . gettype($this->mergedExtractionIds));
        }

        if (1 === count($this->mergedExtractionIds)) {
            throw new Exception("Got only one id provided, must be minimum 2!");
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        if (!EnvReader::isDev()) {
            $this->setHidden();
        }

        $description = "
            Will take multiple extraction ids, merge them into one, assign offers to relate to the new extraction. 
            The new extraction is marked as: " . JobOfferExtraction::STATUS_IMPORTED . ", distance / location 
            ,extraction types etc. are skipped
        ";

        $this->setDescription($description);
        $this->addOption(self::PARAM_EXTRACTION_IDS, null, InputOption::VALUE_REQUIRED, "Extraction ids to merge");
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $canMerge = $this->io->confirm("Do You really want to merge provided extractions into one?");
        if (!$canMerge) {
            throw new Exception("Command execution has been cancelled");
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!EnvReader::isDev()) {
            $this->io->error("This command can only be called on dev env!");
            return self::INVALID;
        }

        if (!$this->lock(self::COMMAND_NAME)){
            $output->writeln("This command is already running");
            return self::SUCCESS;
        }

        try {
            $this->io->info("Started merging multiple job extractions into one");
            $this->entityManager->beginTransaction();

            $extractions = [];
            foreach ($this->mergedExtractionIds as $mergedExtractionId) {
                $extraction = $this->entityManager->find(JobOfferExtraction::class, $mergedExtractionId);
                if (empty($extraction)) {
                    throw new Exception("No extraction exists for provided id: {$mergedExtractionId}");
                }

                $extractions[] = $extraction;
            }

            $newJobOfferExtraction = $this->buildNewExtraction($extractions);

            $this->entityManager->persist($newJobOfferExtraction);
            foreach ($extractions as $oldExtraction) {
                $this->entityManager->remove($oldExtraction);
            }

            $this->entityManager->flush();
            $this->printNewExtractionData($newJobOfferExtraction);

            $this->entityManager->commit();
            $this->io->info("Finished merging multiple job extractions into one");
            $this->release();
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
     * Create new {@see JobOfferExtraction} filled up with data from old extractions
     *
     * @param JobOfferExtraction[] $oldExtractions
     * @return JobOfferExtraction
     */
    private function buildNewExtraction(array $oldExtractions): JobOfferExtraction
    {
        $allOfferIds = [];
        $keywords    = [];
        foreach ($oldExtractions as $oldExtraction) {
            $offerIds = array_map(
                fn(JobSearchResult $jobOffer) => $jobOffer->getId(),
                $oldExtraction->getJobSearchResults()->getValues(),
            );

            $allOfferIds = array_merge($allOfferIds, $offerIds);
            $keywords    = array_merge($keywords,$oldExtraction->getKeywords());
        }

        $keywords            = array_unique($keywords);
        $allOfferIds         = array_unique($allOfferIds);
        $uniqueOfferEntities = array_map(
            fn(int|string $id) => $this->entityManager->find(JobSearchResult::class, $id),
            $allOfferIds
        );

        /** @var JobSearchResult[] $uniqueOfferEntities */
        $uniqueOfferEntities = array_filter($uniqueOfferEntities); // will mostly happen on dev where some offers might be gone

        $newExtraction = new JobOfferExtraction();
        $newExtraction->setKeywords($keywords);
        $newExtraction->setExtractionCount(count($uniqueOfferEntities));
        $newExtraction->setPaginationPagesCount(JobOfferExtraction::PAGINATION_COUNT_UNKNOWN);
        $newExtraction->setStatus(JobOfferExtraction::STATUS_MERGED);

        foreach ($keywords as $keyword) {
            foreach ($uniqueOfferEntities as $offerEntity) {
                $keywordEntity = new ExtractionKeyword2Offer();
                $keywordEntity->setKeyword($keyword);
                $keywordEntity->setExtraction($newExtraction);
                $keywordEntity->setJobOffer($offerEntity);

                $offerEntity->addExtraction($newExtraction);
                $newExtraction->addJobSearchResult($offerEntity);

                if (in_array($offerEntity->getFirstTimeFoundExtraction()->getId(), $this->mergedExtractionIds)) {
                    $offerEntity->setFirstTimeFoundExtraction($newExtraction);
                }

                $offerEntity->addExtractionKeyword($keywordEntity);

                $this->entityManager->persist($keywordEntity);
                $this->entityManager->persist($offerEntity);
            }
        }

        return $newExtraction;
    }

    /**
     * Will print some data about new extraction
     *
     * @param JobOfferExtraction $newJobOfferExtraction
     */
    private function printNewExtractionData(JobOfferExtraction $newJobOfferExtraction)
    {
        $this->io->note("Old extractions have been removed, offers have been merged into new extraction");

        $encodedKeywords = json_encode($newJobOfferExtraction->getKeywords());

        $this->io->listing([
            "Id:  {$newJobOfferExtraction->getId()}",
            "OffersCount:  {$newJobOfferExtraction->getExtractionCount()}",
            "Keywords:  {$encodedKeywords}",
        ]);
    }

}