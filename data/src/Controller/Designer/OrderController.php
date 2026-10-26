<?php
namespace App\Controller\Designer;

use App\Entity\User;
use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    #[Route('/designer/orders', name: 'designer.orders')]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();

        $orders = $this->orderRepository->findOrdersByUser($user);

        return $this->render('designer/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}