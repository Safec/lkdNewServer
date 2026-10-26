<?php


namespace App\Twig\Component;

use App\Entity\User;
use App\Entity\UserRequest;
use App\Repository\UserRequestRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsLiveComponent]
class RequestProcessedSwitch
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly UserRequestRepository $userRequestRepository
    ) {
    }

    #[LiveProp]
    public ?int $requestId = null;

    #[LiveProp(writable: true, hydrateWith: 'isProcessed')]
    public bool $processed = false;

    public function isProcessed(): bool
    {
        if (!$this->requestId) {
            return false;
        }

        $request = $this->userRequestRepository->find($this->requestId);

        return $request instanceof UserRequest && $request->isProcessed();
    }

    #[LiveAction]
    public function changeProcessed(): void
    {
        if (!$this->requestId) {
            return;
        }

        $request = $this->userRequestRepository->find($this->requestId);
        if (!$request instanceof UserRequest) {
            return;
        }

        $request->setProcessed(!$this->processed);
        $this->userRequestRepository->save($request)->flush();

        $this->emit('requestProcessedChange');
    }
}