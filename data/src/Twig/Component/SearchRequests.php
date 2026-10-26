<?php

/** @noinspection PhpUnused */

namespace App\Twig\Component;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\UserRequestRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[Security("is_granted(User::ROLE_MANAGER) or is_granted(User::ROLE_MANAGER_OBR)")]
#[AsLiveComponent]
class SearchRequests
{
    use DefaultActionTrait;

    public function __construct(
        private UserRequestRepository $userRequestRepository,
        public readonly int $gridPerPage = 6
    ) {
        $this->perPage = $this->gridPerPage;
    }

    #[LiveProp(writable: true)]
    public string $query = '';

    /** @var Paginator|UserRequest[] */
    public mixed $userRequests = [];

    #[LiveProp]
    public int $perPage = 6;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public function __invoke(): void
    {
        $paginator = $this->userRequestRepository->search([
            'query' => $this->query,
            'per_page' => $this->perPage,
            'page' => $this->page,
        ]);
        
        $this->userRequests = $paginator;
    }

    #[LiveAction]
    public function onChangeFilter(): void
    {
       $this->page = 1;
       $this->__invoke();
    }

    #[LiveListener('requestProcessedChange')]
    public function onRequestProcessedChange(): void
    {
        $this->__invoke();
    }
}