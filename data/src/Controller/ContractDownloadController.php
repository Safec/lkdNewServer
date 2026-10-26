<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Entity\User;
use App\Service\ContractGeneratorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ContractDownloadController extends AbstractController
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ContractGeneratorService $contractGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/admin/designers/{id}/edit/requisites/contract/download', name: 'admin.designer.requisites.contract_download')]
    #[Route('/designer/requisites/contract/download', name: 'designer.requisites.contract_download')]
    public function __invoke(Request $request, ?User $designer = null): Response
    {
        $user = $this->getUser();
        $user instanceof User || throw $this->createAccessDeniedException();

        if ($designer === null) {
            $designer = $user;
        }

        $isManager = $this->authorizationChecker->isGranted(User::ROLE_MANAGER);
        if (!$isManager) {
            $designer->getId() === $user->getId() || throw $this->createAccessDeniedException();
        }

        $designer->getRequisites()->getId() || throw $this->createNotFoundException();

        try {
            $this->logger->info('Генерирование договора для {designer}.', ['designer' => $designer->getEmail()]);
            $data = $this->contractGenerator->generate($designer->getRequisites());
        } catch (Throwable $error) {
            $this->logger->error($error->getMessage() ?: get_class($error));
            $this->logger->error('Ошибка генерации договора для {designer}.', ['designer' => $designer->getEmail()]);
            throw $this->createNotFoundException(previous: $error);
        }

        if (empty($data['content'])) {
            throw $this->createNotFoundException();
        }

        return new Response($data['content'], headers: array_filter([
            'Content-Type' => $data['mimeType'] ?? null,
            'Content-Disposition' => !empty($data['extension']) ? 'filename="contract.' . $data['extension']  . '"' : null,
        ]));
    }
}