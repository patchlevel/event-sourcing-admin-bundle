<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

use function hash;
use function mt_rand;
use function substr;
use function uniqid;

final class RequestIdListener
{
    public const REQUEST_ID_ATTRIBUTE = '_request-id';

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $requestId = substr(hash('sha256', uniqid((string)mt_rand(), true)), 0, 6);

        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $requestId);
    }
}
