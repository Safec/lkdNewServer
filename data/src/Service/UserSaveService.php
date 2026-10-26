<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserSaveService extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AvatarService $avatarService,
        private readonly LoggerInterface $logger,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {

    }

    public function save(User $user): self
    {
        $requisites = $user->getRequisites();
        $profile = $user->getProfile();

        if (!$requisites->getFullName()) {
            $requisites->setFullName($profile->getFullName());
        }

        if (!$requisites->getPhone()) {
            $requisites->setPhone($profile->getPhone());
        }

        if (!$requisites->getEmail()) {
            $requisites->setEmail($profile->getEmail());
        }

        if (!$requisites->getLegalEntity()) {
            $requisites->setLegalEntity($profile->getLegalEntity());
        }

        $this->userRepository->save($user);

        return $this;
    }

    public function saveProfileForm(FormInterface $form): self
    {
        $user = $form->getData();
        if (!$user instanceof User) {
            throw new InvalidArgumentException('Form data must be instance of ' . User::class . '.');
        }

        // Clone current state of the user
        $currentUserState = $this->userRepository->find($user->getId());
        if (!$currentUserState) {
            throw new \InvalidArgumentException('User not found');
        }

        if ($form->has('avatar_file')) {
            /** @var UploadedFile|mixed $avatarFile */
            $avatarFile = $form->get('avatar_file')->getData();
            if ($avatarFile instanceof UploadedFile) {
                try {
                    $this->logger->debug('Сохраняется аватар пользователя {file}', ['file' => $avatarFile->getClientOriginalName()]);
                    $filepath = $this->avatarService->save($avatarFile);
                    $user->getProfile()->setAvatar($filepath);
                } catch (FilesystemException|Exception $e) {
                    $this->logger->error($e->getMessage() ?: get_class($e));
                    $this->logger->error('Не удалось сохранить аватар пользователя {file}', ['file' => $avatarFile->getClientOriginalName()]);
                }
            }
        }

        if ($form->has('password')) {
            $newPassword = (string)$form->get('password')->getData();
            if (strlen($newPassword)) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
            }
        }

        $this->save($user);

        return $this;
    }

    public function flush(): self
    {
        $this->userRepository->flush();

        return $this;
    }
}