<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\CustomId;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootMetadataFactory;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Snapshot\SnapshotStore;
use Patchlevel\EventSourcing\Store\Criteria;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Store\Stream;
use Patchlevel\Hydrator\Hydrator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Traversable;
use Twig\Environment;

use function json_encode;
use function sprintf;

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
            $aggregateName = $request->request->get('aggregateName');
            $aggregateId = $request->request->get('aggregateId');

            if ($aggregateName === null || $aggregateId === null) {
                throw new NotFoundHttpException('aggregateName and aggregateId are required');
            }

            return new RedirectResponse(
                $this->router->generate('patchlevel_event_sourcing_admin_inspection_show', [
                    'aggregateName' => $request->request->get('aggregateName'),
                    'aggregateId' => $request->request->get('aggregateId'),
                ]),
            );
        }

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/index.html.twig', [
                'aggregates' => $this->aggregateRootRegistry->aggregateNames(),
            ]),
        );
    }

    public function showAction(Request $request, string $aggregateName, string $aggregateId): Response
    {
        $until = null;

        if ($request->query->has('until')) {
            $until = $request->query->getInt('until');
        }

        $tab = $request->query->get('tab', 'details');

        $aggregateClass = $this->aggregateRootRegistry->aggregateClass($aggregateName);
        $aggregate = $this->aggregate($aggregateClass, $aggregateId, $until);

        $criteria = new Criteria(
            aggregateClass: $this->aggregateRootRegistry->aggregateClass($aggregateName),
            aggregateId: $aggregateId,
        );

        $messages = $this->store->load(
            $criteria,
        );

        $count = $this->store->count(
            $criteria,
        );

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
            $snapshot = $this->snapshotStore->load(
                $aggregateClass,
                CustomId::fromString($aggregateId),
            );
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

    /** @param class-string<AggregateRoot> $aggregateClass */
    private function aggregate(string $aggregateClass, string $aggregateId, int|null $until = null): AggregateRoot
    {
        $criteria = new Criteria(
            aggregateClass: $aggregateClass,
            aggregateId: $aggregateId,
        );

        $stream = null;

        try {
            $stream = $this->store->load($criteria);

            $firstMessage = $stream->current();

            if ($firstMessage === null) {
                throw new NotFoundHttpException(
                    sprintf('Aggregate "%s" with the id "%s" not found', $aggregateClass, $aggregateId),
                );
            }

            return $aggregateClass::createFromEvents(
                $this->unpack($stream, $until),
                $firstMessage->playhead() - 1,
            );
        } finally {
            $stream?->close();
        }
    }

    /** @return Traversable<object> */
    private function unpack(Stream $stream, int|null $until = null): Traversable
    {
        foreach ($stream as $message) {
            if ($until !== null && $message->playhead() > $until) {
                break;
            }

            yield $message->event();
        }
    }
}
