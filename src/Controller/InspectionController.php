<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Aggregate\AggregateHeader;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\CustomId;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootMetadataFactory;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Snapshot\SnapshotStore;
use Patchlevel\EventSourcing\Store\Criteria\AggregateIdCriterion;
use Patchlevel\EventSourcing\Store\Criteria\AggregateNameCriterion;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Header\PlayheadHeader;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Store\Stream;
use Patchlevel\EventSourcing\Store\StreamStore;
use Patchlevel\Hydrator\Hydrator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Traversable;
use Twig\Environment;

use function count;
use function json_encode;
use function preg_match;
use function sprintf;
use function str_replace;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class InspectionController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly RouterInterface $router,
        private readonly Store $store,
        private readonly AggregateRootRegistry $aggregateRootRegistry,
        private readonly AggregateRootMetadataFactory $aggregateRootMetadataFactory,
        private readonly Hydrator $hydrator,
        private readonly SnapshotStore $snapshotStore,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            $aggregateName = $request->request->get('aggregate');
            $aggregateId = $request->request->get('aggregateId');

            if ($aggregateName === null || $aggregateId === null) {
                throw new NotFoundHttpException('aggregateName and aggregateId are required');
            }

            return new RedirectResponse(
                $this->router->generate('patchlevel_event_sourcing_admin_inspection_show', [
                    'aggregateName' => $aggregateName,
                    'aggregateId' => $aggregateId,
                ]),
            );
        }

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/index.html.twig', [
                'aggregates' => $this->aggregateRootRegistry->aggregateNames(),
            ]),
        );
    }

    public function streamAction(Request $request, string $stream): Response
    {
        $aggregateClassList = $this->aggregateRootRegistry->aggregateClasses();

        $result = [];

        foreach ($aggregateClassList as $aggregateClass) {
            $metadata = $this->aggregateRootMetadataFactory->metadata($aggregateClass);

            $regex = str_replace(
                '{id}',
                '(.+)',
                $metadata->streamName,
            );

            if (!preg_match('#' . $regex . '#', $stream, $matches)) {
                continue;
            }

            $aggregateId = $matches[1];

            if ($metadata->streamName($aggregateId) !== $stream) {
                continue;
            }

            $result[] = [
                'name' => $metadata->name,
                'id' => $matches[1],
            ];
        }

        if (count($result) === 1) {
            return new RedirectResponse(
                $this->router->generate('patchlevel_event_sourcing_admin_inspection_show', [
                    'aggregateName' => $result[0]['name'],
                    'aggregateId' => $result[0]['id'],
                ]),
            );
        }

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/select.html.twig', [
                'stream' => $stream,
                'result' => $result,
            ]),
        );
    }

    public function showAction(Request $request, string $aggregateName, string $aggregateId): Response
    {
        $criteria = $this->getCriteria($aggregateName, $aggregateId);
        $until = null;

        if ($request->query->has('until')) {
            $until = $request->query->getInt('until');
        }

        $tab = $request->query->getString('tab', 'details');

        $aggregateClass = $this->aggregateRootRegistry->aggregateClass($aggregateName);
        $aggregate = $this->aggregate($aggregateName, $aggregateId, $until);

        $count = $this->store->count($criteria);
        $messages = $this->store->load($criteria);

        try {
            $serializedError = null;
            $serializedAggregate = json_encode(
                $this->hydrator->extract($aggregate),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
            );
        } catch (Throwable $e) {
            $serializedAggregate = null;
            $serializedError = $e->getMessage();
        }

        try {
            $snapshotError = null;
            $snapshot = $this->snapshotStore->load($aggregateClass, CustomId::fromString($aggregateId));
        } catch (Throwable $e) {
            $snapshot = null;
            $snapshotError = $e->getMessage();
        }

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/show.html.twig', [
                'messages' => $messages,
                'aggregate' => $aggregate,
                'aggregateName' => $aggregateName,
                'aggregateId' => $aggregateId,
                'aggregateClass' => $aggregateClass,
                'serializedAggregate' => $serializedAggregate,
                'serializedError' => $serializedError,
                'metadata' => $this->aggregateRootMetadataFactory->metadata($aggregateClass),
                'snapshot' => $snapshot,
                'snapshotError' => $snapshotError,
                'count' => $count,
                'until' => $until,
                'tab' => $tab,
            ]),
        );
    }

    private function aggregate(string $aggregateName, string $aggregateId, int|null $until = null): AggregateRoot
    {
        $criteria = $this->getCriteria($aggregateName, $aggregateId);
        $stream = null;

        try {
            $stream = $this->store->load($criteria);

            $firstMessage = $stream->current();

            if ($firstMessage === null) {
                throw new NotFoundHttpException(
                    sprintf('Aggregate "%s" with the id "%s" not found', $aggregateName, $aggregateId),
                );
            }

            $aggregateClass = $this->aggregateRootRegistry->aggregateClass($aggregateName);

            return $aggregateClass::createFromEvents(
                $this->unpack($stream, $until),
                $this->getPlayhead($firstMessage) - 1,
            );
        } finally {
            $stream?->close();
        }
    }

    /** @return Traversable<object> */
    private function unpack(Stream $stream, int|null $until = null): Traversable
    {
        foreach ($stream as $message) {
            if ($until === null) {
                yield $message->event();
            }

            if ($message->hasHeader(AggregateHeader::class) && $message->header(AggregateHeader::class)->playhead > $until) {
                break;
            }

            if ($message->hasHeader(PlayheadHeader::class) && $message->header(PlayheadHeader::class)->playhead > $until) {
                break;
            }

            yield $message->event();
        }
    }

    private function getCriteria(string $aggregateName, string $aggregateId): Criteria
    {
        if ($this->store instanceof StreamStore) {
            return new Criteria(
                new StreamCriterion(
                    $this->aggregateRootMetadataFactory->metadata(
                        $this->aggregateRootRegistry->aggregateClass($aggregateName),
                    )->streamName($aggregateId),
                ),
            );
        }

        return new Criteria(
            new AggregateNameCriterion($aggregateName),
            new AggregateIdCriterion($aggregateId),
        );
    }

    /**
     * @param Message<object> $message
     *
     * @return positive-int
     */
    public function getPlayhead(Message $message): int
    {
        if ($message->hasHeader(AggregateHeader::class)) {
            return $message->header(AggregateHeader::class)->playhead;
        }

        return $message->header(PlayheadHeader::class)->playhead;
    }
}
