<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserRequest;
use App\Repository\UserRequestRepository;
use App\Twig\Component\SearchRequests;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[Security("is_granted(User::ROLE_MANAGER) or is_granted(User::ROLE_MANAGER_OBR)")]
class UserRequestController extends AbstractController
{
    public function __construct(
        private UserRequestRepository $userRequestRepository,
        public readonly int $gridPerPage = 6
    ) {
        $this->perPage = $this->gridPerPage;
    }

    private function getFieldNames(): array
    {
        return [
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
    }

    #[Route('/admin/user-requests', name: 'admin.requests')]
    public function __invoke(Request $request, EntityManagerInterface $em): Response
    {
        $page = 1;

        $fieldNames = $this->getFieldNames();
        
        // Получаем все записи UserRequest
        $userRequests = $this->userRequestRepository->search([
            'per_page' => $this->perPage,
            'page' => $page,
        ]);
        //$userRequests = $em->getRepository(UserRequest::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/user.request.live.html.twig', [
            'userRequests' => $userRequests,
            'fieldNames'=>$fieldNames,
            'per_page' => $this->perPage,
            'page' => $page
        ]);
    }

    #[Route('/admin/user-requests/{id}', name: 'admin.requests.show', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $fieldNames = $this->getFieldNames();

        $userRequests = $em->getRepository(UserRequest::class)->find($id);

        if (!$userRequests) {
            throw $this->createNotFoundException('The requested record does not exist.');
        }

        return $this->render('admin/user.request.html.twig', [
            'userRequests' => [$userRequests],
            'fieldNames'=>$fieldNames,
            'alone' => 1
        ]);
    }
}