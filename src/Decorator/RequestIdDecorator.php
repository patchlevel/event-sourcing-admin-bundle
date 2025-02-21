<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Decorator;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Repository\MessageDecorator\MessageDecorator;
use Patchlevel\EventSourcingAdminBundle\Listener\RequestIdListener;
use Patchlevel\EventSourcingAdminBundle\Message\Header\RequestIdHeader;
use Symfony\Component\HttpFoundation\RequestStack;

use function is_string;

final class RequestIdDecorator implements MessageDecorator
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(Message $message): Message
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            return $message;
        }

        $requestId = $request->attributes->get(RequestIdListener::REQUEST_ID_ATTRIBUTE);

        if (!$requestId) {
            return $message;
        }

        if (!is_string($requestId)) {
            return $message;
        }

        return $message->withHeader(new RequestIdHeader($requestId));
    }
}
