<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Requisites;

use App\Entity\Requisites;
use App\Entity\User;
use App\Form\Designer\LegalEntityType;
use App\Repository\RequisitesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function Symfony\Component\Translation\t;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class LegalEntityController extends AbstractController
{
    public function __construct(
        private readonly RequisitesRepository $requisitesRepository,
    ) {
    }

    #[Route('/admin/designers/{id}/edit/requisites/legal-entity', name: 'admin.designer.requisites.legal_entity')]
    public function __invoke(Request $request, User $designer): Response
    {
        $requisites = $designer->getRequisites();

        $form = $this->createForm(LegalEntityType::class, $requisites, ['save_label' => t('Continue')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isSubmitted()) {
            /** @var Requisites $requisites */
            $requisites = $form->getData();
            $this->requisitesRepository->save($requisites)->flush();
            $this->addFlash('success', 'Форма собственности сохранена.');

            return $this->redirectToRoute('admin.designer.requisites.personal_data', ['id' => $designer->getId()]);
        }

        return $this->render('admin/designer.requisites.legal_entity.html.twig', [
            'designer' => $designer,
            'form' => $form,
        ]);
    }
}