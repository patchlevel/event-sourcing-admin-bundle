<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\EventBus\ListenerDescriptor;
use Patchlevel\EventSourcing\EventBus\ListenerProvider;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Metadata\Subscriber\SubscriberMetadataFactory;
use Patchlevel\EventSourcingAdminBundle\Projection\Node;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class EventController
{
    /**
     * @param iterable<object> $subscribers
     */
    public function __construct(
        private readonly Environment               $twig,
        private readonly EventRegistry             $eventRegistry,
        private readonly ListenerProvider          $listenerProvider,
        private readonly iterable                  $subscribers,
        private readonly SubscriberMetadataFactory $subscriberMetadataFactory,
    )
    {
    }

    public function indexAction(): Response
    {
        $events = [];

        foreach ($this->eventRegistry->eventClasses() as $eventName => $eventClass) {
            $events[] = [
                'name' => $eventName,
                'class' => $eventClass,
                'listeners' => $this->listenerMethods($eventClass),
                'subscribers' => $this->subscribersMethods($eventClass),
            ];
        }

        return new Response($this->twig->render('@PatchlevelEventSourcingAdmin/event/index.html.twig', [
            'events' => $events,
        ]));
    }

    private function listenerMethods(string $eventClass): array
    {
        return array_map(
            static fn(ListenerDescriptor $listener) => $listener->name(),
            $this->listenerProvider->listenersForEvent($eventClass),
        );
    }

    private function subscribersMethods(string $eventClass): array
    {
        $result = [];

        foreach ($this->subscribers as $subscriber) {
            $metadata = $this->subscriberMetadataFactory->metadata($subscriber::class);

            if (array_key_exists($eventClass, $metadata->subscribeMethods)) {
                foreach ($metadata->subscribeMethods[$eventClass] as $method) {
                    $result[] = sprintf('%s::%s', $subscriber::class, $method->name);
                }
            }

            if (array_key_exists(Subscribe::ALL, $metadata->subscribeMethods)) {
                foreach ($metadata->subscribeMethods[Subscribe::ALL] as $method) {
                    $result[] = sprintf('%s::%s', $subscriber::class, $method->name);
                }
            }
        }

        return $result;
    }

    /**
     * @param string $eventClass
     * @return list<Node>
     */
    private function source(string $eventClass): array
    {
        $node = $this->findNodeByEventClass($eventClass);

        if (!$node) {
            return [];
        }

        return $this->findSources($node);
    }

    private function findNodeByEventClass(string $eventClass): Node|null
    {
        if ($this->traceProjector === null) {
            return null;
        }

        $nodes = $this->traceProjector->nodes();

        $name = $this->eventRegistry->eventName($eventClass);

        foreach ($nodes as $node) {
            if ($node->name === $name) {
                return $node;
            }
        }

        return null;
    }

    private function findNodeById(string $id): Node|null
    {
        if ($this->traceProjector === null) {
            return null;
        }

        $nodes = $this->traceProjector->nodes();

        foreach ($nodes as $node) {
            if ($node->id === $id) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @param Node $node
     * @return list<Node>
     */
    private function findSources(Node $node): array
    {
        $links = $this->traceProjector->links();

        $result = [];

        foreach ($links as $link) {
            if ($link->toId === $node->id) {
                $result[] = $this->findNodeById($link->fromId);
            }
        }

        return $result;
    }
}
