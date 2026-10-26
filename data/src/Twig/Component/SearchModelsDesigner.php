<?php

/** @noinspection PhpUnused */

namespace App\Twig\Component;

use App\Entity\Model;
use App\Repository\ModelRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class SearchModelsDesigner
{
    use DefaultActionTrait;

    public function __construct(
        private readonly ModelRepository $modelRepository,
        public readonly int $gridPerPage = 15
    ) {
        $this->perPage = $this->gridPerPage;
    }

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(writable: true)]
    public string $sort = 'fullName';

    #[LiveProp(writable: true)]
    public string $sortDirection = 'asc';

    #[LiveProp(writable: true)]
    public string $view = 'grid';

    /** @var Paginator|Model[] */
    public mixed $models = [];

    /** @var Paginator|Categories[] */
    public mixed $categories = [];

    #[LiveProp(writable: true)]
    public array $selectedSubcategoryIds = []; // Для хранения выбранных подкатегорий

    #[LiveProp]
    public int $perPage = 15;

    #[LiveProp(writable: true)]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public ?int $categoryId = null;

    #[LiveProp(writable: true)]
    public array $subcategoryIds = [];

    public function __invoke(): void
    {
        /*if (!$this->authorizationChecker->isGranted(User::ROLE_MANAGER)) {
            return;
        }*/

        $paginator = $this->modelRepository->search(
            [
                'query' => trim($this->query),
                'per_page' => $this->perPage,
                'page' => $this->page,
            ],
            $this->sort,
            $this->sortDirection
        );

        $this->models = $paginator;
    }

    #[LiveAction]
    public function onChangeFilter(): void
    {
        $this->page = 1;
        $this->__invoke();
    }

}