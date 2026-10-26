<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class UtmListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) return;

        $utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
        foreach ($utmKeys as $key) {
            if ($request->query->has($key)) {
                $value = $request->query->get($key);
                $request->getSession()->set($key, $value);
            }
        }

        if ($request->cookies->has('_ym_uid')) {
            $request->getSession()->set('ymClientId', $request->cookies->get('_ym_uid'));
        }
    }
}