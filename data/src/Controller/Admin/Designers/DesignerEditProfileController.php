<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers;

use App\Entity\User;
use App\Form\Designer\ProfileType;
use App\Service\UserSaveService;
use Psr\Log\LoggerInterface;
use App\Service\PhoneParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DesignerEditProfileController extends AbstractController
{
    public function __construct(
        private readonly UserSaveService $userSaveService,
        private readonly LoggerInterface $logger,
        private readonly NotifierInterface $notifier
    ) {
    }

    #[Route('/admin/designers/{id}/edit/profile', name: 'admin.designer.profile')]
    public function __invoke(Request $request, User $designer): Response
    {
        $designer instanceof User || throw $this->createNotFoundException();
        $designer->hasRole(User::ROLE_DESIGNER) || throw $this->createNotFoundException();
        $currentDesignerActive = $designer->isActive();
        $formOptions = [
            'without_fields' => ['userAgreementAccepted','userAgreementAcceptedReklama'],
            'with_optional_fields' => ['active'],
        ];

        if (!$this->isGranted(User::ROLE_BOSS)) {
            $formOptions['disabled_fields'][] = 'phone';
        }

        $form = $this->createForm(ProfileType::class, $designer, $formOptions);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userSaveService->saveProfileForm($form)->flush();

                if (!$currentDesignerActive && $form->has('active') && $form->get('active')->getData() == '1') {
                    $phone = PhoneParser::normalizeOrExcept((string)$form->get('phone')->getData());
                    //$notification = new Notification('Ваш профиль успешно активирован', ['sms']);
                    $recipient = new Recipient(phone: $phone);
                    //$this->notifier->send($notification, $recipient);
                    $this->logger->info('Создано уведомление DesignEditProfile: '. print_r($recipient, true));
                    //$this->addFlash('success', 'Смс об активации успешно отправлено.');
                }
                $this->addFlash('success', 'Профиль успешно сохранен.');
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage() ?: get_class($e));
                $this->logger->error('Ошибка сохранения профиля пользователя #{id}.', ['id' => $designer->getId()]);
                $this->addFlash('error', 'Не удалось сохранить профиль.');
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('admin/designer.profile.html.twig', [
            'form' => $form,
            'designer' => $designer,
        ]);
    }
}