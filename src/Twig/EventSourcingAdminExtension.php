<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Twig;

use DateTimeImmutable;
use Patchlevel\EventSourcing\Aggregate\AggregateHeader;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Message\HeaderNotFound;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Serializer\Encoder\JsonEncoder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\EventSourcing\Store\Header\PlayheadHeader;
use Patchlevel\EventSourcing\Store\Header\RecordedOnHeader;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Store\StreamStore;
use Patchlevel\EventSourcingAdminBundle\Message\Header\RequestIdHeader;
use Patchlevel\EventSourcingAdminBundle\TokenMapper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function array_pop;
use function explode;
use function get_class;

final class EventSourcingAdminExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /** @return list<class-string> */
    public static function getSubscribedServices(): array
    {
        return [
            AggregateRootRegistry::class,
            EventRegistry::class,
            EventSerializer::class,
            TokenMapper::class,
            Store::class,
        ];
    }

    private function aggregateRootRegistry(): AggregateRootRegistry
    {
        return $this->container->get(AggregateRootRegistry::class);
    }

    private function eventRegistry(): EventRegistry
    {
        return $this->container->get(EventRegistry::class);
    }

    private function eventSerializer(): EventSerializer
    {
        return $this->container->get(EventSerializer::class);
    }

    private function tokenMapper(): TokenMapper
    {
        return $this->container->get(TokenMapper::class);
    }

    private function store(): Store
    {
        return $this->container->get(Store::class);
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('eventsourcing_aggregate_class', $this->aggregateClass(...)),
            new TwigFunction('eventsourcing_aggregate_name', $this->aggregateName(...)),
            new TwigFunction('eventsourcing_aggregate_id', $this->aggregateId(...)),
            new TwigFunction('eventsourcing_uses_stream_store', $this->usesStreamStore(...)),
            new TwigFunction('eventsourcing_stream_name', $this->streamName(...)),
            new TwigFunction('eventsourcing_playhead', $this->playhead(...)),
            new TwigFunction('eventsourcing_recorded_on', $this->recordedOn(...)),
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

    /**
     * @param Message<object> $message
     *
     * @return class-string<AggregateRoot>
     */
    public function aggregateClass(Message $message): string
    {
        return $this->aggregateRootRegistry()->aggregateClass($message->header(AggregateHeader::class)->aggregateName);
    }

    /** @param Message<object> $message */
    public function aggregateName(Message $message): string
    {
        return $message->header(AggregateHeader::class)->aggregateName;
    }

    /** @param Message<object> $message */
    public function aggregateId(Message $message): string
    {
        return $message->header(AggregateHeader::class)->aggregateId;
    }

    public function usesStreamStore(): bool
    {
        return $this->store() instanceof StreamStore;
    }

    /** @param Message<object> $message */
    public function streamName(Message $message): string
    {
        try {
            return $message->header(StreamNameHeader::class)->streamName;
        } catch (HeaderNotFound) {
            return $message->header(AggregateHeader::class)->streamName();
        }
    }

    /** @param Message<object> $message */
    public function playhead(Message $message): int|null
    {
        try {
            return $message->header(PlayheadHeader::class)->playhead;
        } catch (HeaderNotFound) {
        }

        try {
            return $message->header(AggregateHeader::class)->playhead;
        } catch (HeaderNotFound) {
        }

        return null;
    }

    /** @param Message<object> $message */
    public function recordedOn(Message $message): DateTimeImmutable|null
    {
        try {
            return $message->header(RecordedOnHeader::class)->recordedOn;
        } catch (HeaderNotFound) {
        }

        try {
            return $message->header(AggregateHeader::class)->recordedOn;
        } catch (HeaderNotFound) {
        }

        return null;
    }

    /**
     * @param Message<T> $message
     *
     * @return class-string<T>
     *
     * @template T of object
     */
    public function eventClass(Message $message): string
    {
        return get_class($message->event());
    }

    /** @param Message<object> $message */
    public function eventName(Message $message): string
    {
        return $this->eventRegistry()->eventName($this->eventClass($message));
    }

    /** @param Message<object> $message */
    public function eventPayload(Message $message): string
    {
        return $this->eventSerializer()->serialize(
            $message->event(),
            [JsonEncoder::OPTION_PRETTY_PRINT => true],
        )->payload;
    }

    /** @param Message<object> $message */
    public function profilerToken(Message $message): string|null
    {
        try {
            $requestIdHeader = $message->header(RequestIdHeader::class);
        } catch (HeaderNotFound) {
            return null;
        }

        return $this->tokenMapper()->get($requestIdHeader->requestId);
    }
}
