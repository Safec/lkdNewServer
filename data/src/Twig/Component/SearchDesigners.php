<?php

/** @noinspection PhpUnused */

namespace App\Twig\Component;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class SearchDesigners
{
    use DefaultActionTrait;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        public readonly int $gridPerPage = 6,
        public readonly int $tablePerPage = 100,
    ) {
        $this->perPage = $this->gridPerPage;
    }

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(writable: true)]
    public bool $onlyNew = false;

    #[LiveProp(writable: true)]
    public string $sort = 'fullName';

    #[LiveProp(writable: true)]
    public string $sortDirection = 'asc';

    #[LiveProp(writable: true)]
    public string $view = 'grid';

    /** @var Paginator|User[] */
    public mixed $designers = [];

    #[LiveProp]
    public int $perPage = 6;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public function __invoke(): void
    {
        if (!$this->authorizationChecker->isGranted(User::ROLE_MANAGER)) {
            return;
        }

        $paginator = $this->userRepository->search(
            [
                'role' => User::ROLE_DESIGNER,
                'query' => trim($this->query),
                'only_new' => $this->onlyNew,
                'per_page' => $this->perPage,
                'page' => $this->page,
            ],
            $this->sort,
            $this->sortDirection
        );

        $this->designers = $paginator;
    }

    #[LiveAction]
    public function onChangeFilter(): void
    {
        $this->page = 1;
        $this->__invoke();
    }

    #[LiveAction]
    public function onChangeView(Request $request): void
    {
        $this->page = 1;
        if ($this->view === 'table') {
            $request->getSession()->set('admin.designers.view', 'table');
            $this->perPage = $this->tablePerPage;
        } else {
            $request->getSession()->set('admin.designers.view', 'grid');
            $this->perPage = $this->gridPerPage;
        }

        $this->__invoke();
    }

    #[LiveListener('userActiveChange')]
    public function onUserActiveChange(): void
    {
        $this->__invoke();
    }
}