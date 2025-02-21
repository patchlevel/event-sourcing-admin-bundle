<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Listener;

use Patchlevel\EventSourcingAdminBundle\TokenMapper;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class TokenMapperListener
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

        if ($debugToken === null) {
            return;
        }

        $requestId = $event->getRequest()->attributes->getString(RequestIdListener::REQUEST_ID_ATTRIBUTE);

        if (!$requestId) {
            return;
        }

        $this->tokenMapper->set($requestId, $debugToken);
    }
}
