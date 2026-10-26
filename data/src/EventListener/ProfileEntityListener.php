<?php

/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */

namespace App\EventListener;

use App\Entity\Profile;
use App\Service\AvatarService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Throwable;

#[AsEntityListener(entity: Profile::class)]
class ProfileEntityListener
{
    public function __construct(
        private readonly AvatarService $avatarService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function preUpdate(Profile $profile, PreUpdateEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события preUpdate для профиля "{name}".', ['name' => $profile->getFullName()]);

            if ($event->hasChangedField('avatar')) {
                $oldAvatar = (string)$event->getOldValue('avatar');
                $newAvatar = (string)$event->getNewValue('avatar');
                if ($oldAvatar && $oldAvatar !== $newAvatar) {
                    try {
                        $this->logger->info('Удаляется аватар {avatar}.', ['avatar' => $oldAvatar]);
                        $this->avatarService->delete($oldAvatar);
                    } catch (FilesystemException|Exception $error) {
                        $this->logger->error($error->getMessage() ?: get_class($error));
                        $this->logger->error('Не удалось удалить аватар {avatar}.', ['avatar' => $oldAvatar]);
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие preUpdate для профиля "{name}".', ['name' => $profile->getFullName()]);
        }
    }

    public function postRemove(Profile $profile, PostRemoveEventArgs $event): void
    {
        try {
            $this->logger->debug('Обработка события postRemove для профиля "{name}".', ['name' => $profile->getFullName()]);

            if ($profile->getAvatar()) {
                try {
                    $this->logger->info('Удаляется аватар {avatar}.', ['avatar' => $profile->getAvatar()]);
                    $this->avatarService->delete((string)$profile->getAvatar());
                } catch (FilesystemException|Exception $error) {
                    $this->logger->error($error->getMessage() ?: get_class($error));
                    $this->logger->error('Не удалось удалить аватар {avatar}.', ['avatar' => $profile->getAvatar()]);
                }
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage() ?: get_class($e));
            $this->logger->error('Не удалось обработать событие postRemove для профиля "{name}".', ['name' => $profile->getFullName()]);
        }
    }
}