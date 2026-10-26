<?php

/** @noinspection PhpUnused */

namespace App\EventListener;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Throwable;

#[AsEntityListener(entity: User::class)]
class UserEntityListener
{
    public function __construct(
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function preUpdate(User $user, PreUpdateEventArgs $event): void
    {
        try {
            $this->logger->info('Сохраняются в Change Log изменения пользователя {user}.', ['user' => $user->getUserIdentifier()]);

            $changeSet = $event->getEntityChangeSet();
            $changeSet = array_filter($changeSet, function ($key) {
                return !in_array($key, ['changeLog', 'updatedAt']);
            });

            if (!$changeSet) {
                return;
            }

            $author = $this->security->getUser();
            if (!$author instanceof User) {
                return;
            }

            foreach ($changeSet as $key => $values) {
                if ($key === 'password' && is_array($values)) {
                    $changeSet[$key] = array_map(function ($value) {
                        return !empty($value) ? '***' : '';
                    }, $values);
                }
            }

            $now = new DateTime();

            $changeLog = $user->getChangeLog() ?? [];
            $changeLog[] = [
                'author' => $author->getLogin() ?: $author->getEmail() ?: $author->getPhone() ?: 'unknown',
                'date' => $now->format('Y-m-d H:i:s'),
                'changes' => $changeSet,
            ];

            $user->setChangeLog($changeLog);

            if (($changeSet['active'][1] ?? null) === true) {
                $user->setActivatedBy($author->getProfile()->getFullName() ?: $author->getLogin() ?: $author->getEmail() ?: $author->getPhone() ?: 'unknown');
                $user->setActivatedAt($now);
            }

            if (($changeSet['status'][1] ?? null) === User::STATUS_MODERATE) {
                $user->setModerateBy($author->getProfile()->getFullName() ?: $author->getLogin() ?: $author->getEmail() ?: $author->getPhone() ?: 'unknown');
            }
        } catch (Throwable $error) {
            $this->logger->error($error->getMessage() ?: get_class($error));
            $this->logger->error('Не удалось сохранить в Change Log изменения пользователя {user}.', ['user' => $user->getUserIdentifier()]);
        }
    }
}