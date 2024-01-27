<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Aggregate\CustomId;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Store\Criteria;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\Hydrator\Hydrator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class InspectionController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Store $store,
        private readonly RepositoryManager $repositoryManager,
        private readonly AggregateRootRegistry $aggregateRootRegistry,
        private readonly Hydrator $hydrator,
    ) {
    }

    public function indexAction(): Response
    {
        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/index.html.twig', [
                'aggregates' => $this->aggregateRootRegistry->aggregateNames(),
            ]),
        );
    }

    public function showAction(Request $request, string $aggregateName, string $aggregateId): Response
    {
        $aggregateClass = $this->aggregateRootRegistry->aggregateClass($aggregateName);
        $repository = $this->repositoryManager->get($aggregateClass);
        $aggregate = $repository->load(CustomId::fromString($aggregateId));

        $criteria = new Criteria(
            aggregateClass: $this->aggregateRootRegistry->aggregateClass($aggregateName),
            aggregateId: $aggregateId,
        );

        $messages = $this->store->load(
            $criteria,
        );

        try {
            $serializedAggregate = json_encode(
                $this->hydrator->extract($aggregate),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
            );
        } catch (\Throwable $e) {
            $serializedAggregate = null;
        }

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/show.html.twig', [
                'messages' => $messages,
                'aggregate' => $aggregate,
                'aggregateName' => $aggregateName,
                'aggregateId' => $aggregateId,
                'aggregateClass' => $aggregateClass,
                'serializedAggregate' => $serializedAggregate,
            ]),
        );
    }
}
