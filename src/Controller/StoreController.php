<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Store\Criteria;
use Patchlevel\EventSourcing\Store\Store;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class StoreController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Store $store,
        private readonly AggregateRootRegistry $aggregateRootRegistry,
    ) {
    }

    public function showAction(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);

        $criteria = $this->criteria($request);

        $messages = $this->store->load(
            $criteria,
            $limit,
            ($page - 1) * $limit,
            true,
        );

        $count = $this->store->count($criteria);

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/store/show.html.twig', [
                'messages' => $messages,
                'count' => $count,
                'aggregates' => $this->aggregateRootRegistry->aggregateNames(),
                'limit' => $limit,
                'page' => $page,
            ]),
        );
    }

    private function criteria(Request $request): Criteria
    {
        $aggregateName = $request->query->get('aggregate');
        $aggregateId = $request->query->get('aggregateId');

        return new Criteria(
            aggregateName: $aggregateName,
            aggregateId: $aggregateId,
        );
    }
}
