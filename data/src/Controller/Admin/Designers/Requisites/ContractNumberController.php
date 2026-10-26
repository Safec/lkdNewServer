<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Requisites;

use App\Entity\Requisites;
use App\Entity\User;
use App\Form\Designer\ContractNumberType;
use App\Repository\RequisitesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class ContractNumberController extends AbstractController
{
    public function __construct(
        private readonly RequisitesRepository $requisitesRepository,
    ) {
    }

    #[Route('/admin/designers/{id}/edit/requisites/contract-number', name: 'admin.designer.requisites.contract_number')]
    public function __invoke(Request $request, User $designer): Response
    {
        $requisites = $designer->getRequisites();

        $form = $this->createForm(ContractNumberType::class, $requisites);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Requisites $requisites */
            $requisites = $form->getData();
            $this->requisitesRepository->save($requisites)->flush();
            $this->addFlash('success', 'Номер договора сохранен.');

            return $this->redirect($request->getUri());
        }

        return $this->render('admin/designer.requisites.contract_number.html.twig', [
            'designer' => $designer,
            'form' => $form,
        ]);
    }
}
