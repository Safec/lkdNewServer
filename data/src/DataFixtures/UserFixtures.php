<?php

/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */

namespace App\DataFixtures;

use App\Entity\Document;
use App\Entity\Draft;
use App\Entity\Project;
use App\Entity\Requisites;
use App\Entity\User;
use App\Service\PrivateUploadService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const BOSS_EMAIL = 'boss@example.com';
    public const BOSS_PHONE = '+79990000002';
    public const BOSS_PASSWORD = 'boss_password';
    public const MANAGER_EMAIL = 'manager@example.com';
    public const MANAGER_PHONE = '+79990000003';
    public const MANAGER_PASSWORD = 'manager_password';
    public const DESIGNER_EMAIL = 'designer@example.com';
    public const DESIGNER_PHONE = '+79990000004';
    public const DESIGNER_PASSWORD = 'designer_password';
    public const NEW_DESIGNER_PHONE = '+79990000005';
    public const UNREGISTERED_PHONE = '+79990000006';
    public const UNEXPECTED_USER_EMAIL = 'unexpected@example.com';
    public const UNEXPECTED_USER_PHONE = '+79990000007';
    public const UNEXPECTED_USER_PASSWORD = 'unexpected_password';
    public const DESIGNER_SELF_EMPLOYED_EMAIL = 'self-employed@example.com';
    public const DESIGNER_SELF_EMPLOYED_PHONE = '+79990000008';
    public const DESIGNER_SELF_EMPLOYED_PASSWORD = 'self_employed_password';
    public const DESIGNER_INDIVIDUAL_EMAIL = 'individual@example.com';
    public const DESIGNER_INDIVIDUAL_PHONE = '+79990000009';
    public const DESIGNER_INDIVIDUAL_PASSWORD = 'individual_password';
    public const DESIGNER_LTD_EMAIL = 'ltd@example.com';
    public const DESIGNER_LTD_PHONE = '+79990000010';
    public const DESIGNER_LTD_PASSWORD = 'ltd_password';
    public const DESIGNER_WITH_PRIVATE_DOCUMENT_EMAIL = 'designer_with_document@example.com';
    public const DESIGNER_WITH_PRIVATE_DOCUMENT_PHONE = '+79990000011';
    public const DESIGNER_WITHOUT_REQUISITES_EMAIL = 'without_requisites@example.com';
    public const DESIGNER_WITHOUT_REQUISITES_PHONE = '+79990000012';
    public const DESIGNER_FULLY_REGISTERED_EMAIL = 'registered@example.com';
    public const DESIGNER_FULLY_REGISTERED_PHONE = '+79990000013';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PrivateUploadService $privateUploadService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail(self::BOSS_EMAIL);
        $user->setPhone(self::BOSS_PHONE);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::BOSS_PASSWORD));
        $user->setRoles([User::ROLE_BOSS]);
        $user->setActive(true);
        $user->setName('Босс');
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::MANAGER_EMAIL);
        $user->setPhone(self::MANAGER_PHONE);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::MANAGER_PASSWORD));
        $user->setRoles([User::ROLE_MANAGER]);
        $user->setActive(true);
        $user->setName('Менеджер');
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::DESIGNER_EMAIL);
        $user->setPhone(self::DESIGNER_PHONE);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DESIGNER_PASSWORD));
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setActive(true);
        $user->setName('Дизайнер');
        $manager->persist($user);

        $draft = new Draft();
        $draft->setAuthor($user);
        $draft->setTitle('Тестовый черновик');
        $manager->persist($draft);

        $project = new Project();
        $project->setAuthor($user);
        $project->setTitle('Тестовый проект');
        $manager->persist($project);

        $user = new User();
        $user->setEmail('');
        $user->setPhone(self::NEW_DESIGNER_PHONE);
        $user->setPassword(null);
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setActive(false);
        $user->setName('Новый дизайнер');
        $user->getProfile()->setFullName($user->getName());
        $user->getRequisites()->setLegalEntity(Requisites::LEGAL_ENTITY_SELF_EMPLOYED);
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::UNEXPECTED_USER_EMAIL);
        $user->setPhone(self::UNEXPECTED_USER_PHONE);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::UNEXPECTED_USER_PASSWORD));
        $user->setRoles(['ROLE_UNEXPECTED']);
        $user->setActive(true);
        $user->setName('Непредвиденная роль');
        $user->getProfile()->setLegalEntity('unexpected');
        $user->getRequisites()->setLegalEntity('unexpected');
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::DESIGNER_SELF_EMPLOYED_EMAIL);
        $user->setPhone(self::DESIGNER_SELF_EMPLOYED_PHONE);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DESIGNER_SELF_EMPLOYED_PASSWORD));
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setActive(true);
        $user->setName('Дизайнер самозанятый');
        $user->getProfile()->setLegalEntity(Requisites::LEGAL_ENTITY_SELF_EMPLOYED);
        $user->getRequisites()->setLegalEntity(Requisites::LEGAL_ENTITY_SELF_EMPLOYED);
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::DESIGNER_INDIVIDUAL_EMAIL);
        $user->setPhone(self::DESIGNER_INDIVIDUAL_PHONE);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DESIGNER_INDIVIDUAL_PASSWORD));
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setActive(true);
        $user->setName('Дизайнер индивидуальный предприниматель');
        $user->getProfile()->setLegalEntity(Requisites::LEGAL_ENTITY_INDIVIDUAL);
        $user->getRequisites()->setLegalEntity(Requisites::LEGAL_ENTITY_INDIVIDUAL);
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::DESIGNER_LTD_EMAIL);
        $user->setPhone(self::DESIGNER_LTD_PHONE);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DESIGNER_LTD_PASSWORD));
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setActive(true);
        $user->setName('ООО "Дизайнер"');
        $user->getProfile()->setLegalEntity(Requisites::LEGAL_ENTITY_LTD);
        $user->getRequisites()->setLegalEntity(Requisites::LEGAL_ENTITY_LTD);
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::DESIGNER_WITH_PRIVATE_DOCUMENT_EMAIL);
        $user->setPhone(self::DESIGNER_WITH_PRIVATE_DOCUMENT_PHONE);
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setActive(true);
        $user->setName('Дизайнер с приватным документом');
        $manager->persist($user);

        ob_start();
        imagejpeg(imagecreatetruecolor(100, 100));
        $image = ob_get_clean();
        $this->privateUploadService->privateStorage->write('tmp/test_manager_private_file.jpg', $image);

        $document = new Document();
        $document->setRelatedUser($user);
        $document->setFiles(['tmp/test_manager_private_file.jpg']);
        $document->setName('test');
        $manager->persist($document);

        $user = new User();
        $user->setEmail(self::DESIGNER_WITHOUT_REQUISITES_EMAIL);
        $user->setPhone(self::DESIGNER_WITHOUT_REQUISITES_PHONE);
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setName('Дизайнер без реквизитов');
        $user->setActive(false);
        $user->getProfile()->setFullName($user->getName());
        $manager->persist($user);

        $user = new User();
        $user->setEmail(self::DESIGNER_FULLY_REGISTERED_EMAIL);
        $user->setPhone(self::DESIGNER_FULLY_REGISTERED_PHONE);
        $user->setRoles([User::ROLE_DESIGNER]);
        $user->setName('Дизайнер подтвержденный');
        $user->setActive(true);
        $user->getProfile()->setFullName($user->getName());
        $user->getRequisites()->setLegalEntity(Requisites::LEGAL_ENTITY_SELF_EMPLOYED);
        $user->getRequisites()->setContractNumber('TEST-123');
        $manager->persist($user);

        $manager->flush();
    }
}
