<?php

/** @noinspection PhpUnused */

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
    name: 'app:sms:send',
    description: 'Send SMS.',
)]
class SmsSendCommand extends Command
{
    public function __construct(
        private readonly NotifierInterface $notifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('phone', InputArgument::REQUIRED, 'Phone number.')
            ->addArgument('message', InputArgument::OPTIONAL, 'Message.', 'Test message.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $phone = (string)$input->getArgument('phone');
        $message = (string)$input->getArgument('message');

        $notification = new Notification($message, ['sms']);
        $recipient = new Recipient(phone: $phone);

        $io->note(sprintf('Sending SMS to %s...', $phone));
        $this->notifier->send($notification, $recipient);

        return Command::SUCCESS;
    }
}
