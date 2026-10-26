<?php

/** @noinspection PhpUnused */

namespace App\Controller\Admin\Designers\Drafts;

use App\Entity\Draft;
use App\Entity\User;
use App\Form\Designer\DraftType;
use App\Service\DraftSaveService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DraftEditController extends AbstractController
{
    public function __construct(
        private readonly DraftSaveService $draftSaveService,
    ) {
    }

    #[Route('/admin/designers/drafts/{id}/edit', name: 'admin.designer.draft.edit')]
    public function __invoke(Request $request, Draft $draft): Response
    {
        $form = $this->createForm(DraftType::class, $draft);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Draft $draft */
            $draft = $form->getData();
            $this->draftSaveService->save($draft)->flush();
            $this->addFlash('success', "Черновик «{$draft->getTitle()}» сохранен.");

            return $this->redirect($request->getUri());
        }

        if ($draft->getAuthor()?->getId()) {
            $returnPath = $this->generateUrl('admin.designer.drafts', ['id' => $draft->getAuthor()?->getId()]);
        } else {
            $returnPath = $this->generateUrl('admin.designers');
        }

        return $this->render('admin/designer.draft.edit.html.twig', [
            'form' => $form,
            'return_path' => $returnPath,
        ]);
    }
}