<?php

/** @noinspection PhpUnused */

namespace App\Twig\Component;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Message;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsLiveComponent]
class UserActiveSwitch
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly NotifierInterface $notifier
    ) {
    }

    #[LiveProp]
    public ?int $userId = null;

    #[LiveProp(writable: true, hydrateWith: 'isActive')]
    public bool $active = false;

    public function isActive(): bool
    {
        if (!$this->userId) {
            return false;
        }

        $user = $this->userRepository->find($this->userId);

        return $user instanceof User && $user->isActive();
    }

    #[LiveAction]
    public function changeActive(): void
    {
        if (!$this->userId) {
            return;
        }

        $user = $this->userRepository->find($this->userId);
        if (!$user instanceof User) {
            return;
        }

        $old_active = $this->active;

        $isBoss = $this->authorizationChecker->isGranted(User::ROLE_BOSS);
        $isManager = $this->authorizationChecker->isGranted(User::ROLE_MANAGER);
        if ($isBoss || ($isManager && !$user->hasRole(User::ROLE_BOSS) && !$user->hasRole(User::ROLE_MANAGER))) {
            $user->setActive(!$this->active);
            $this->userRepository->save($user)->flush();
        }

        $this->emit('userActiveChange');
    }
}