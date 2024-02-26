<?php

namespace Patchlevel\EventSourcingAdminBundle\Decorator;

use Patchlevel\EventSourcing\Repository\MessageDecorator\MessageDecorator;
use Patchlevel\EventSourcing\EventBus\Message;
use Patchlevel\EventSourcingAdminBundle\Listener\RequestIdListener;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestIdDecorator implements MessageDecorator
{
    public function __construct(
        private readonly RequestStack $requestStack
    )
    {
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

        return $message->withHeader('requestId', $requestId);
    }
}
