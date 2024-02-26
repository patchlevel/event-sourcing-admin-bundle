<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\EventBus\ListenerDescriptor;
use Patchlevel\EventSourcing\EventBus\ListenerProvider;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Metadata\Projector\ProjectorMetadataFactory;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class EventController
{
    /**
     * @param iterable<object> $projectors
     */
    public function __construct(
        private readonly Environment              $twig,
        private readonly EventRegistry            $eventRegistry,
        private readonly ListenerProvider         $listenerProvider,
        private readonly iterable                 $projectors,
        private readonly ProjectorMetadataFactory $projectorMetadataFactory,
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
                'listeners' => array_map(
                    static fn(ListenerDescriptor $listener) => $listener->name(),
                    $this->listenerProvider->listenersForEvent($eventClass),
                ),
                'projectors' => $this->projectorsMethods($eventClass),
            ];
        }

        return new Response($this->twig->render('@PatchlevelEventSourcingAdmin/event/index.html.twig', [
            'events' => $events,
        ]));
    }

    private function projectorsMethods(string $eventClass): array
    {
        $result = [];

        foreach ($this->projectors as $projector) {
            $metadata = $this->projectorMetadataFactory->metadata($projector::class);

            if (array_key_exists($eventClass, $metadata->subscribeMethods)) {
                $result[] = sprintf('%s::%s', $projector::class, $metadata->subscribeMethods[$eventClass]);
            }
        }

        return $result;
    }
}
