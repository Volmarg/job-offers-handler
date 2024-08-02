<?php

namespace JobSearcher\Command\Debug;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JobSearcher\Command\AbstractCommand;
use JobSearcher\Entity\JobSearchResult\JobSearchResult;
use JobSearcher\Service\CompanyData\CompanyDataService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

/**
 * Command for testing random things
 */
class DebugCommand extends AbstractCommand
{
    private const COMMAND_NAME = "debug:general";

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return self::COMMAND_NAME;
    }

    /**
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        LoggerInterface        $logger,
        private EntityManagerInterface $entityManager,
        private CompanyDataService $companyDataService
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this->setDescription("Command to debug random stuff, may contain different code on call time");
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $builder  = $this->entityManager->createQueryBuilder();
            /** @var $entities JobSearchResult[] */
            $entities = $builder->select("jbs")
                ->from(JobSearchResult::class, "jbs")
                ->where("jbs.id = 25069")
                ->getQuery()->execute();

            $first = $entities[0];
        } catch(Exception | TypeError $e){
            $this->logger->critical("Exception was thrown while calling command", [
                "class" => self::class,
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}