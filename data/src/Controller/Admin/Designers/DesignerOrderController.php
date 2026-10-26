<?php

namespace App\Controller\Admin\Designers;

use App\Entity\User;
use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

#[IsGranted(User::ROLE_MANAGER)]
class DesignerOrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        public readonly int $gridPerPage = 6
    ) {
        $this->perPage = $this->gridPerPage;
    }



    #[Route('/admin/orders', name: 'admin.designers.order_list')]
    public function __invoke(Request $request, EntityManagerInterface $em): Response
    {
        $page = 1;

        $userOrders = $this->orderRepository->search([
            'per_page' => $this->perPage,
            'page' => $page,
        ]);

        return $this->render('admin/designer.orders.live.html.twig', [
            'userOrders' => $userOrders,
            'per_page' => $this->perPage,
            'page' => $page
        ]);
    }

    #[Route('/admin/orders/import-orders', name: 'admin.designers.import_orders')]
    public function importOrders(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $form = $this->createFormBuilder()
            ->add('file', FileType::class, [
                'label' => 'Выберите XLSX файл',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                        'mimeTypesMessage' => 'Пожалуйста, загрузите файл в формате XLSX',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Загрузить'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

                // Очистка таблицы заказов
    		$em->createQuery('DELETE FROM App\Entity\Order o')->execute();
            $added = $updated = $notFound = 0;

            foreach ($rows as $index => $row) {
                if ($index === 1) continue; // Пропускаем заголовки

                [$phone, $orderNumber, $status, $date, $amount, $actReady] = array_values($row);

                $normalizedPhone = preg_replace('/\D/', '', $phone);
                // Приводим к формату +7, если начинается с 8 или 7
                if (strlen($normalizedPhone) == 11) {
                    if ($normalizedPhone[0] == '8') {
                        $normalizedPhone = '7' . substr($normalizedPhone, 1);
                    }
                    $normalizedPhone = '+'.$normalizedPhone; // Добавляем "+"
                }

                $user = $userRepository->findOneBy(['phone' => $normalizedPhone]);

                if (!$user) {
                    $notFound++;
                    continue;
                }

                $order = $em->getRepository(Order::class)->findOneBy([
                        'user' => $user,
                        'orderNumber' => $orderNumber
                    ]) ?? new Order();

                $order->setUser($user);
                $order->setOrderNumber($orderNumber);
                $order->setStatus($status);
                $order->setDate($date?(new \DateTime($date)):null);
                $order->setAmount((float)preg_replace('/\D/', '', $amount));
                $order->setActReady($actReady?$actReady:'');

                if ($order->getId() === null) {
                    $em->persist($order);
                    $added++;
                } else {
                    $updated++;
                }
            }

            $em->flush();

            $this->addFlash('success', "Добавлено: $added, обновлено: $updated, не найдено: $notFound");
            return $this->redirectToRoute('admin.designers.order_list');
        }

        return $this->render('admin/designer.import_orders.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/designers/{id}/orders', name: 'admin.designer.orders')]
    public function designerOrders(Request $request, User $designer): Response
    {
        $orders = $this->orderRepository->findOrdersByUser($designer);

        return $this->render('admin/designer.orders.html.twig', [
        	'designer' => $designer,
            'orders' => $orders,
        ]);
    }
}