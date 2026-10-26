<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsCommand(
    name: 'app:email:send',
    description: 'Send Email.',
)]
class EmailSendCommand extends Command
{
    public function __construct(
        private readonly NotifierInterface $notifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address.')
            ->addArgument('message', InputArgument::OPTIONAL, 'Message.', 'Test message.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string)$input->getArgument('email');
        $message = (string)$input->getArgument('message');

        // Create an email notification
        $notification = (new Notification($message, ['email']))
            ->content($message)
            ->subject('Subject of the email');

        $recipient = new Recipient($email);

        $io->note(sprintf('Sending Email to %s...', $email));
        $this->notifier->send($notification, $recipient);

        $io->success('Email sent successfully!');

        return Command::SUCCESS;
    }
}