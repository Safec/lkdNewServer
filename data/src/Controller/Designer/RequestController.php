<?php


namespace App\Controller\Designer;

use App\Entity\UserRequest;
use App\Entity\User;
use App\Form\Designer\OrderNotificationType;
use App\Form\Designer\DiscountRequestType;
use App\Form\Designer\ModelsRequestType;
use App\Form\Designer\QuestionRequestType;
use App\Service\Bitrix24DesignerService;
use App\Service\DocumentSaveService;
use App\Repository\UserRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Message;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RequestController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private readonly \App\Service\PrivateUploadService $privateUploadService,
        private UserRequestRepository $userRequestRepository,
        private readonly Bitrix24DesignerService $bitrix24Service
    ) {
    }

    #[Route('/designer/order-notification', name: 'designer.order_notification')]
    public function orderNotification(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        // Создаем URL для action
        $actionUrl = $this->urlGenerator->generate('designer.order_notification');

        // Передаем URL в метод createForm
        $form = $this->createForm(OrderNotificationType::class, null, [
            'action' => $actionUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $uploadedFiles = []; $userPhone = '';
            foreach (['invoice', 'project_info'] as $field) {
                if (isset($data[$field])) {
                    if ($data[$field] instanceof \App\Entity\Document) {
                        $document = $data[$field];
                        $files = $document->getFiles();

                        foreach ($files as $fileData) {
                            if (isset($fileData['file']))
                                if ($fileData['file'] instanceof UploadedFile) {
                                    $fileName = $this->privateUploadService->upload($fileData['file'], 'forms');

                                    //$file = $fileData['file'];
                                    //$fileName = uniqid() . '.' . $file->guessExtension();
                                    //$file->move($this->getParameter('uploads_directory'), $fileName);
                                    $uploadedFiles[$field][] = $fileName;
                                } elseif (isset($fileData['path']) && !empty($fileData['path'])) {
                                    $uploadedFiles[$field][] = $fileData['path'];
                                }
                        }
                    } else {
                        // Тип данных не совпадает
                       // dump('The field ' . $field . ' is not an instance of Document');
                    }
                } else {
                    // Поле не установлено
                   // dump('The field ' . $field . ' is not set');
                }
            }

            // Сохранение данных в БД
            $userRequest = new UserRequest();
            $userRequest->setUser($user);
            $userRequest->setCreatedAt(new \DateTime());

            $data['productList'] = nl2br(htmlspecialchars($data['productList']));
            $data['attached'] = $uploadedFiles;
            $data['type'] = 'Уведомление о заказе';

            $userRequest->setData($data);

            $em->persist($userRequest);
            $em->flush();

            $this->sendToBitrix24(
                $user,
                'Уведомление о заказе',
                $data,
                $uploadedFiles
            );

            // Отправка email
            $email = (new Email())
                ->from('info.design.ask@mail.ru')
                ->to('askona_design@askona.ru')
                ->subject('Уведомление о заказе от '.$user->getRequisites()->getFullName().', Email: '.$user->getRequisites()->getEmail().(($userPhone=$user->getPhone())?' Телефон: '.$userPhone:''))
                ->html($this->renderView('emails/order_notification.html.twig', ['data' => $data]));

            // Добавление вложений
            if ($uploadedFiles)
                foreach ($uploadedFiles as $field => $files) {
                    foreach ($files as $fileName) {
                        $email->attachFromPath( $this->getParameter('kernel.project_dir').'/uploads/'.$fileName);
                    }
                }

            $this->mailer->send($email);

            //$this->addFlash('success', 'Ваше уведомление о заказе успешно отправлено. В ближайшее время менеджер его рассмотрит и даст ответ.');

            return $this->render('designer/alerts/order_notification_success.html.twig');
            //return $this->redirectToRoute('designer.order_notification');
        } else {
            //$this->addFlash('error', 'Ошибка сохранения');
            //return new Response();
        }

        return $this->render('designer/order_notification.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/designer/discount-request', name: 'designer.discount_request')]
    public function discountRequest(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        // Создаем URL для action
        $actionUrl = $this->urlGenerator->generate('designer.discount_request');

        // Передаем URL в метод createForm
        $form = $this->createForm(DiscountRequestType::class, null, [
            'action' => $actionUrl,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $uploadedFiles = [];
            foreach (['invoice', 'project_info'] as $field) {
                if (isset($data[$field])) {
                    if ($data[$field] instanceof \App\Entity\Document) {
                        $document = $data[$field];
                        $files = $document->getFiles();

                        foreach ($files as $fileData) {
                            if (isset($fileData['file']))
                                if ($fileData['file'] instanceof UploadedFile) {
                                    $fileName = $this->privateUploadService->upload($fileData['file'], 'forms');

                                    //$file = $fileData['file'];
                                    //$fileName = uniqid() . '.' . $file->guessExtension();
                                    //$file->move($this->getParameter('uploads_directory'), $fileName);
                                    $uploadedFiles[$field][] = $fileName;
                                } elseif (isset($fileData['path']) && !empty($fileData['path'])) {
                                    $uploadedFiles[$field][] = $fileData['path'];
                                }
                        }
                    } else {
                        // Тип данных не совпадает
                        // dump('The field ' . $field . ' is not an instance of Document');
                    }
                } else {
                    // Поле не установлено
                    // dump('The field ' . $field . ' is not set');
                }
            }

            // Сохранение данных в БД
            $userRequest = new UserRequest();
            $userRequest->setUser($user);
            $userRequest->setCreatedAt(new \DateTime());

            $data['productList'] = nl2br(htmlspecialchars($data['productList']));
            $data['attached'] = $uploadedFiles;
            $data['type'] = 'Запрос скидки для клиента';

            $userRequest->setData($data);

            $em->persist($userRequest);
            $em->flush();

            $this->sendToBitrix24(
                $user,
                'Запрос скидки',
                $data,
                $uploadedFiles
            );

            // Отправка email
            $email = (new Email())
                ->from('info.design.ask@mail.ru')
                ->to('askona_design@askona.ru')
                ->subject('Запрос скидки от '.$user->getRequisites()->getFullName().', Email: '.$user->getRequisites()->getEmail())
                ->html($this->renderView('emails/discount_request.html.twig', ['data' => $data]));

            // Добавление вложений
            if ($uploadedFiles)
                foreach ($uploadedFiles as $field => $files) {
                    foreach ($files as $fileName) {
                        $email->attachFromPath( $this->getParameter('kernel.project_dir').'/uploads/'.$fileName);
                    }
                }

            $this->mailer->send($email);

            //$this->addFlash('success', 'Ваш запрос о скидке успешно отправлен. В ближайшее время менеджер его рассмотрит и даст ответ.');

            return $this->render('designer/alerts/discount_request_success.html.twig');
            //return $this->redirectToRoute('designer.discount_request');
            // return $this->redirectToRoute('/designer/order_notification');
        } else {
            //$this->addFlash('error', 'Ошибка сохранения');
           // return new Response();
        }

        return $this->render('designer/discount_request.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/designer/models-request', name: 'designer.models_request')]
    public function modelsRequest(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        $form = $this->createForm(ModelsRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Сохранение данных в БД
            $userRequest = new UserRequest();
            $userRequest->setUser($user);
            $userRequest->setCreatedAt(new \DateTime());

            $data['type'] = 'Запрос 3D модели';

            $userRequest->setData($data);

            $em->persist($userRequest);
            $em->flush();

            $this->sendToBitrix24(
                $user,
                'Запрос 3D модели',
                $data,
                []
            );

            // Отправка email
            $email = (new Email())
                ->from('info.design.ask@mail.ru')
                ->to('askona_design@askona.ru')
                ->subject('Запрос 3D модели от '.$user->getRequisites()->getFullName().', Email: '.$user->getRequisites()->getEmail())
                ->html($this->renderView('emails/models_request.html.twig', ['data' => $data]));

            $this->mailer->send($email);
            //$this->addFlash('success', 'Ваше запрос успешно отправлен. В ближайшее время менеджер его рассмотрит и даст ответ.');

            return $this->render('designer/alerts/model_request_success.html.twig');
        }

        return $this->render('designer/models_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/designer/question-request', name: 'designer.question_request')]
    public function questionRequest(Request $request, EntityManagerInterface $em, UserInterface $user): Response
    {
        $form = $this->createForm(QuestionRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Сохранение данных в БД
            $userRequest = new UserRequest();
            $userRequest->setUser($user);
            $userRequest->setCreatedAt(new \DateTime());

            $data['type'] = 'Задан вопрос';

            $userRequest->setData($data);

            $em->persist($userRequest);
            $em->flush();

            $this->sendToBitrix24(
                $user,
                'Вопрос',
                $data,
                []
            );

            // Отправка email
            $email = (new Email())
                ->from('info.design.ask@mail.ru')
                ->to('askona_design@askona.ru')
                ->subject('Вопрос от: '.$user->getRequisites()->getFullName().', Email: '.$user->getRequisites()->getEmail())
                ->html($this->renderView('emails/question_request.html.twig', ['data' => $data]));

            $this->mailer->send($email);
            //$this->addFlash('success', 'Ваше вопрос успешно отправлено. В ближайшее время менеджер его рассмотрит и даст ответ.');

            return $this->render('designer/alerts/question_request_success.html.twig');
        }

        return $this->render('designer/question_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/designer/history-request', name: 'designer.history_request')]
    public function historyRequest(UserInterface $user): Response
    {
        $userId = $user->getId();
        $requests = $this->userRequestRepository->findRequestsByUser($userId);

        // Decode JSON data if it is a string
        foreach ($requests as $request) {
            $data = $request->getData();
            if (is_string($data)) {
                $request->setData(json_decode($data, true));
            }
        }

        $fieldNames = [
            'fullName' => 'Заказчик',
            'phone' => 'Телефон заказчика',
            'address' => 'Адрес доставки/объекта',
            'productList' => 'Предварительный список товаров',
            'question' => 'Вопрос',
            'way' => 'Куда отправить ответ',
            'link' => 'Ссылка на нужный товар на сайте',
            'sale' => 'Размер скидки клиента',
            'type' => 'Тип запроса',
            'attached' => 'Приложенные файлы',
            'invoice' => 'Счет',
            'project_info'=> 'Информация о проекте'
        ];

        return $this->render('designer/history_request.html.twig', [
            'requests' => $requests,
            'fieldNames'=>$fieldNames
        ]);
    }

    /**
     * Отправляет данные запроса в смарт-процесс «Уведомление».
     *
     * @param array $data Данные формы
     * @param array $uploadedFiles ['invoice' => ['file1.jpg', 'file2.pdf'], 'project_info' => [...]]
     */
    private function sendToBitrix24(
        UserInterface $user,
        string $type,
        array $data,
        array $uploadedFiles = []
    ): void {
        if (!$user instanceof User) {
            return;
        }
        $requisites = $user->getRequisites();

        $notificationData = [
            'title'           => sprintf('%s от %s', $type, $requisites?->getFullName() ?? $user->getUserIdentifier()),
            'assignedById'    => 16,
            'designerPhone'   => $user->getPhone(),
            'clientFullName'  => $data['fullName'] ?? null,
            'clientPhone'     => $data['phone'] ?? null,
            'deliveryAddress' => $data['address'] ?? null,
            'project'         => $this->buildProjectDescription($type, $data),
            'opened'          => 'Y',
        ];

        $filePaths = [];
        if (!empty($uploadedFiles['project_info'])) {
            foreach ($uploadedFiles['project_info'] as $fileName) {
                $filePaths[] = $this->getParameter('kernel.project_dir') . '/uploads/' . $fileName;
            }
        }

        $this->bitrix24Service->createNotificationItemWithFiles($notificationData, $filePaths);
    }

    /**
     * Формирует текст описания проекта из полей формы.
     */
    private function buildProjectDescription(string $type, array $data): string
    {
        $lines = ['Тип: ' . $type];

        if (!empty($data['productList'])) {
            $lines[] = 'Товары: ' . strip_tags($data['productList']);
        }
        if (!empty($data['sale'])) {
            $lines[] = 'Скидка: ' . $data['sale'];
        }
        if (!empty($data['question'])) {
            $lines[] = 'Вопрос: ' . $data['question'];
        }
        if (!empty($data['link'])) {
            $lines[] = 'Ссылка: ' . $data['link'];
        }
        if (!empty($data['way'])) {
            $lines[] = 'Куда ответить: ' . $data['way'];
        }

        return implode("\n", $lines);
    }

}