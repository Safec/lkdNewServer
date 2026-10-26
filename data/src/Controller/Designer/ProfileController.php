<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer;

use App\Entity\User;
use App\Form\Designer\ProfileType;
use App\Service\AvatarService;
use App\Service\UserSaveService;
use App\Service\PhoneParser;
use App\Service\Bitrix24DesignerService;
use Exception;
use League\Flysystem\FilesystemException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_DESIGNER)]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly UserSaveService $userSaveService,
        private readonly AvatarService $avatarService,
        private readonly Bitrix24DesignerService $bitrix24Service,
    ) {
    }

    #[Route('/designer/profile', name: 'designer.profile')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();

        $hasRequisites = $user->getRequisites()->getLegalEntity() !== null;

        // Запоминаем, был ли профиль заполнен ДО сабмита формы
        $wasFioFilled = $user->getProfile()->getFullName() !== null;

        $form = $this->createForm(ProfileType::class, $user, [
            'disabled_fields' => [
                'phone'
            ],
            'without_fields' => array_filter([
                $user->getProfile()->isUserAgreementAccepted() ? 'userAgreementAccepted' : '',
                $user->getProfile()->isUserAgreementAcceptedReklama() ? 'userAgreementAcceptedReklama' : '',
            ]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();

            /** @var UploadedFile|mixed $avatarFile */
            $avatarFile = $form->get('avatar_file')->getData();
            if ($avatarFile instanceof UploadedFile) {
                try {
                    $filepath = $this->avatarService->save($avatarFile);
                    $user->getProfile()->setAvatar($filepath);
                } catch (FilesystemException|Exception) {
                    $this->addFlash('error', 'Ошибка загрузки аватара.');
                }
            }

            $this->userSaveService->save($user)->flush();

            // ---- Отправка в Битрикс24 только при ПЕРВОМ заполнении ФИО ----
            if (!$wasFioFilled && $user->getProfile()->getFullName() !== null) {
                $this->syncDesignerToBitrix24($user);
            }

            $this->addFlash('success', 'Профиль успешно сохранен.');

            return $this->redirect($hasRequisites ? $request->getUri() : $this->generateUrl('designer.requisites'));
        }

        return $this->render('designer/profile.html.twig', [
            'form' => $form,
            'has_requisites' => $hasRequisites,
        ]);
    }

    /**
     * Синхронизация дизайнера с Битрикс24: поиск по телефону → создание или пропуск.
     */
    private function syncDesignerToBitrix24(User $user): void
    {
        $profile = $user->getProfile();
        $phone = $profile->getPhone();

        if ($phone === null || $phone === '') {
            return; // Без телефона искать/создавать нечего
        }

        $phone = trim((string)$phone);
        $phone === '' ? null : PhoneParser::normalize($phone);

        // 1. Проверяем, нет ли уже дизайнера с таким телефоном
        $existingId = $this->bitrix24Service->findDesignerByPhone($phone);

        if ($existingId !== null) {
            // Дизайнер уже есть — не создаём дубликат
            return;
        }

        // 2. Формируем данные для Битрикс24
        $designerData = [
            'fullName'     => $profile->getFullName(),
            'phone'        => $phone,
            'email'        => $profile->getEmail(),
            'telegram'     => $profile->getTelegram(),
            'city'         => $profile->getCity(),
            'assignedById' => 16, // TODO: вынести в конфиг
            'sourceId'     => 'WEB',
            'opened'       => 'Y',
            'sourceCustom' => 'Форма регистрации lkd.askona.ru',
        ];

        // 3. Создаём дизайнера
        $designerId = $this->bitrix24Service->createDesignerItem($designerData);

        if ($designerId === null) {
            $this->addFlash('error', 'Не удалось создать запись в CRM. Обратитесь к администратору.');
            return;
        }

        // 4. Создаём уведомление (опционально — если оно нужно сразу)
        // $this->bitrix24Service->createNotificationItem([...]);
    }

}
