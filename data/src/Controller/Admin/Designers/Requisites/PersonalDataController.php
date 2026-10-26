<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Requisites;

use App\Entity\Requisites;
use App\Entity\User;
use App\Form\Designer\CompanyType;
use App\Form\Designer\IndividualType;
use App\Form\Designer\SelfEmployedType;
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
class PersonalDataController extends AbstractController
{
    public function __construct(
        private readonly RequisitesRepository $requisitesRepository,
    ) {
    }

    #[Route('/admin/designers/{id}/edit/requisites/personal-data', name: 'admin.designer.requisites.personal_data')]
    public function __invoke(Request $request, User $designer): Response
    {
        $requisites = $designer->getRequisites();
        $designer->setRequisites($requisites);

        if ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_LTD) {
            $formType = CompanyType::class;
            $template = 'admin/designer.requisites.company.html.twig';
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_INDIVIDUAL) {
            $formType = IndividualType::class;
            $template = 'admin/designer.requisites.individual.html.twig';
        } elseif ($requisites->getLegalEntity() === Requisites::LEGAL_ENTITY_SELF_EMPLOYED) {
            $formType = SelfEmployedType::class;
            $template = 'admin/designer.requisites.self_employed.html.twig';
        } else {
            $this->addFlash('warning', 'Сначала нужно выбрать форму собственности.');

            return $this->redirectToRoute('admin.designer.requisites.legal_entity', ['id' => $designer->getId()]);
        }

        $form = $this->createForm($formType, $requisites, ['save_label' => t('Continue')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Requisites $requisites */
            $requisites = $form->getData();
            $this->requisitesRepository->save($requisites)->flush();
            $this->addFlash('success', 'Личные данные сохранены.');

            return $this->redirectToRoute('admin.designer.requisites.documents_upload', ['id' => $designer->getId()]);
        }

        return $this->render($template, [
            'designer' => $designer,
            'form' => $form,
        ]);
    }
}
