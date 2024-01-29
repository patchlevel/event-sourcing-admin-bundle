<?php

namespace Patchlevel\EventSourcingAdminBundle\Listener;

use Patchlevel\EventSourcingAdminBundle\TokenMapper;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class TokenMapperListener
{
    public function __construct(
        private readonly TokenMapper $tokenMapper,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        $debugToken = $response->headers->get('X-Debug-Token');

        if (!$debugToken) {
            return;
        }

        $requestId = $event->getRequest()->attributes->get(RequestIdListener::REQUEST_ID_ATTRIBUTE);

        if (!$requestId) {
            return;
        }

        $this->tokenMapper->set($requestId, $debugToken);
    }
}
