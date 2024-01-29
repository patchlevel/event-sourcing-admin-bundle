<?php

namespace Patchlevel\EventSourcingAdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestIdListener
{
    public const REQUEST_ID_ATTRIBUTE = '_request-id';

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $requestId = substr(hash('sha256', uniqid(mt_rand(), true)), 0, 6);

        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $requestId);
    }
}
