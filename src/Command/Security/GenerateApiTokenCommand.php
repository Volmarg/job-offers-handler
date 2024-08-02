<?php

namespace JobSearcher\Command\Security;

use JobSearcher\Command\AbstractCommand;
use JobSearcher\Repository\Security\ApiUserRepository;
use JobSearcher\Service\Jwt\UserJwtTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateApiTokenCommand extends AbstractCommand
{
    private const OPTION_USER_NAME = 'user-name';
    private const OPTION_NO_EXPIRATION = "no-expiration";

    protected function getCommandName(): string
    {
        return "security:generate-api-token";
    }

    public function __construct(
        private readonly ApiUserRepository   $apiUserRepository,
        private readonly UserJwtTokenService $userJwtTokenService,
        LoggerInterface                      $logger,
    )
    {
        parent::__construct($logger);
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Will generate temporary api jwt token")
            ->addOption(self::OPTION_USER_NAME, null, InputOption::VALUE_REQUIRED, 'User name for which token should be created')
            ->addOption(self::OPTION_NO_EXPIRATION, null, InputOption::VALUE_NONE, "If set then the jwt will have no expiration time");
    }

    /**
     * @throws JWTEncodeFailureException
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws JWTEncodeFailureException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isEndless = $input->hasOption(self::OPTION_NO_EXPIRATION) ?? false;

        $userName = $input->getOption(self::OPTION_USER_NAME);
        if (empty($userName)) {
            $io->error("Missing username");
            return self::INVALID;
        }

        $user = $this->apiUserRepository->findOneByName($userName);
        if (empty($user)) {
            $io->error("No user was found for provided username: {$userName}");
            return self::INVALID;
        }

        $jwtToken = $this->userJwtTokenService->generate($user, $isEndless);

        $io->info("Your token");

        $output->writeln('');
        $output->writeln($jwtToken);
        $output->writeln('');


        return Command::SUCCESS;
    }

}
