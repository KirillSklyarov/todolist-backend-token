<?php

namespace App\Command;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserCreateCommand extends Command
{
    protected static $defaultName = 'app:user:create';

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    public function __construct(UserRepository $userRepository,
                                TokenRepository $tokenRepository,
                                $name = null)
    {
        parent::__construct($name);
        $this->userRepository = $userRepository;
        $this->tokenRepository = $tokenRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
//            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
//            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
//        $arg1 = $input->getArgument('arg1');
//
//        if ($arg1) {
//            $io->note(sprintf('You passed an argument: %s', $arg1));
//        }
//
//        if ($input->getOption('option1')) {
//            // ...
//        }

        $now = new \DateTime();
        $username = Uuid::uuid4()->toString();
        $username = 'e36db462-d5a7-401e-8067-020a344ab9ca';
//        $user = (new User())
//            ->setUsername($username);
        $user = $this->userRepository->findOneBy(['username' => $username]);

//        $token = (new Token())
//            ->setValue(Uuid::uuid4()->toString())
//            ->setCreatedAt($now)
//            ->setLastEnterAt($now);
//        $value = '22bdebd1-c4de-4f95-b64c-b42ccf7fdef5';
//        $token = $this->tokenRepository->findOneBy(['value' => $value]);

//        $user->addToken($token);
        $this->userRepository->delete($user);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
