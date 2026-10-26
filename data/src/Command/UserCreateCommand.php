<?php

/** @noinspection PhpUnused */

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Create user with given credentials.',
)]
class UserCreateCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Password')
            ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'Login')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email')
            ->addOption('phone', null, InputOption::VALUE_REQUIRED, 'Phone')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Role')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $login = (string)$input->getOption('login');
        $email = (string)$input->getOption('email');
        $phone = (string)$input->getOption('phone');
        if (!$login && !$email && !$phone) {
            $io->error('Login, email or phone are required.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setLogin($login);
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setRoles([$input->getOption('role')]);
        $user->setActive(true);

        $passwordHash = $this->passwordHasher->hashPassword($user, (string)$input->getOption('password'));
        $user->setPassword($passwordHash);

        $this->userRepository->save($user)->flush();

        if ($user->getId()) {
            $io->success('User #' . (string)$user->getId() . ' created.');
            return Command::SUCCESS;
        } else {
            $io->error('User not created.');
            return Command::FAILURE;
        }
    }
}
