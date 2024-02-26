<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Projection\Projectionist\Projectionist;
use Patchlevel\EventSourcing\Projection\Projectionist\ProjectionistCriteria;
use Patchlevel\EventSourcing\Store\Store;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class ProjectionController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Projectionist $projectionist,
        private readonly Store $store,
        private readonly RouterInterface $router,
    ) {
    }

    public function showAction(): Response
    {
        $projections = $this->projectionist->projections();
        $messageCount = $this->store->count();

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/projection/show.html.twig', [
                'projections' => $projections,
                'messageCount' => $messageCount,
            ]),
        );
    }

    public function rebuildAction(string $id): Response
    {
        $criteria = new ProjectionistCriteria([$id]);

        $this->projectionist->remove($criteria);
        $this->projectionist->boot($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_eventsourcing_admin_projection_show'),
        );
    }
}
