<?php

/** @noinspection PhpUnused */

namespace App\Controller\Designer\Drafts;

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
#[IsGranted(User::ROLE_DESIGNER)]
class DraftEditController extends AbstractController
{
    public function __construct(
        private readonly DraftSaveService $draftSaveService,
    ) {
    }

    #[Route('/designer/new-draft', name: 'designer.draft.new')]
    #[Route('/designer/drafts/{slug}/edit', name: 'designer.draft.edit')]
    public function __invoke(Request $request, ?Draft $draft = null): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();
        $user->isActive() || throw $this->createAccessDeniedException();

        if ($draft) {
            $draft->getAuthor()?->getId() == $user->getId() || throw $this->createAccessDeniedException();
        } else {
            $draft = new Draft();
            $draft->setAuthor($user);
        }

        $form = $this->createForm(DraftType::class, $draft);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Draft $draft */
            $draft = $form->getData();
            $this->draftSaveService->save($draft)->flush();
            $this->addFlash('success', "Черновик «{$draft->getTitle()}» сохранен.");

            return $this->redirectToRoute('designer.drafts');
        }

        return $this->render('designer/draft.edit.html.twig', [
            'form' => $form,
        ]);
    }
}