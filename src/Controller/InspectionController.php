<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Aggregate\CustomId;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Store\Criteria;
use Patchlevel\EventSourcing\Store\Store;
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
    ) {
    }

    public function showAction(Request $request): Response
    {
        $aggregateName = $request->query->get('aggregate');
        $aggregateId = $request->query->get('aggregateId');

        if (!$aggregateName || !$aggregateId) {
            return new Response(
                $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/show.html.twig', [
                    'messages' => null,
                    'aggregate' => null,
                    'aggregates' => $this->aggregateRootRegistry->aggregateNames(),
                ]),
            );
        }

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

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/inspection/show.html.twig', [
                'messages' => $messages,
                'aggregate' => $aggregate,
                'aggregates' => $this->aggregateRootRegistry->aggregateNames(),
            ]),
        );
    }
}
