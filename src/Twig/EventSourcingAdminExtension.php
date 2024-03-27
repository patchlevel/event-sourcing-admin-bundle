<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Twig;

use Patchlevel\EventSourcing\Aggregate\AggregateHeader;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Serializer\Encoder\JsonEncoder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\EventSourcingAdminBundle\TokenMapper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function array_pop;
use function explode;
use function get_class;

final class EventSourcingAdminExtension extends AbstractExtension
{
    public function __construct(
        private readonly AggregateRootRegistry $aggregateRootRegistry,
        private readonly EventRegistry $eventRegistry,
        private readonly EventSerializer $eventSerializer,
        private readonly TokenMapper $tokenMapper,
    ) {
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('eventsourcing_aggregate_class', $this->aggregateClass(...)),
            new TwigFunction('eventsourcing_event_class', $this->eventClass(...)),
            new TwigFunction('eventsourcing_event_name', $this->eventName(...)),
            new TwigFunction('eventsourcing_event_payload', $this->eventPayload(...)),
            new TwigFunction('eventsourcing_short_id', $this->shortId(...)),
            new TwigFunction('eventsourcing_profiler_token', $this->profilerToken(...)),
        ];
    }

    public function shortId(string $id): string
    {
        $parts = explode('-', $id);

        return array_pop($parts);
    }

    /** @return class-string */
    public function aggregateClass(Message $message): string
    {
        return $this->aggregateRootRegistry->aggregateClass($message->header(AggregateHeader::class)->aggregateName);
    }

    /** @return class-string */
    public function eventClass(Message $message): string
    {
        return get_class($message->event());
    }

    public function eventName(Message $message): string
    {
        return $this->eventRegistry->eventName(
            $this->eventClass($message),
        );
    }

    public function eventPayload(Message $message): string
    {
        return $this->eventSerializer->serialize(
            $message->event(),
            [JsonEncoder::OPTION_PRETTY_PRINT => true],
        )->payload;
    }

    public function profilerToken(Message $message): ?string
    {
        return null;

        $headers = $message->customHeaders();
        $requestId = $headers['requestId'] ?? null;

        if (!$requestId) {
            return null;
        }

        return $this->tokenMapper->get($requestId);
    }
}
