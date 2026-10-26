<?php

/** @noinspection PhpUnused */

namespace App\Twig\Component;

use App\Service\PhoneParser;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsLiveComponent]
class UserStatusSelect extends AbstractController
{
    use ComponentToolsTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly NotifierInterface $notifier,
        private readonly LoggerInterface $logger
    ) {
    }

    #[LiveProp(writable: ['status'])]
    public User $user;

    public function __invoke(): void
    {
        $isBoss = $this->authorizationChecker->isGranted(User::ROLE_BOSS);
        $isManager = $this->authorizationChecker->isGranted(User::ROLE_MANAGER);
        if ($isBoss || ($isManager && !$this->user->hasRole(User::ROLE_BOSS) && !$this->user->hasRole(User::ROLE_MANAGER))) {
            $this->userRepository->save($this->user)->flush();
        }

        if ($this->user->isActive()) {
            $phone = PhoneParser::normalizeOrExcept($this->user->getPhone());
            //$notification = new Notification('Ваш профиль успешно активирован', ['sms']);
            $recipient = new Recipient(phone: $phone);
            $this->logger->info('Создано уведомление UserStatusSelect: ' . print_r($recipient, true));
            //$this->notifier->send($notification, $recipient);
            //$this->addFlash('success', 'Смс об активации успешно отправлено.');
        }


        $this->emit('userActiveChange');
    }
}