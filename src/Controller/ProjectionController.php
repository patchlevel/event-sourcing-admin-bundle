<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Projection\Projection\ProjectionStatus;
use Patchlevel\EventSourcing\Projection\Projection\RunMode;
use Patchlevel\EventSourcing\Projection\Projectionist\Projectionist;
use Patchlevel\EventSourcing\Projection\Projectionist\ProjectionistCriteria;
use Patchlevel\EventSourcing\Store\Store;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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

    public function showAction(Request $request): Response
    {
        $projections = $this->projectionist->projections();
        $messageCount = $this->store->count();

        $groups = [];

        foreach ($projections as $projection) {
            $groups[$projection->group()] = true;
        }

        $filteredProjections = [];
        $search = $request->get('search');
        $group = $request->get('group');
        $mode = $request->get('mode');
        $status = $request->get('status');


        foreach ($projections as $projection) {
            if ($search && !str_contains($projection->id(), $search)) {
                continue;
            }

            if ($group && $projection->group() !== $group) {
                continue;
            }

            if ($mode && $projection->runMode()->value !== $mode) {
                continue;
            }

            if ($status && $projection->status()->value !== $status) {
                continue;
            }

            $filteredProjections[] = $projection;
        }

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/projection/show.html.twig', [
                'projections' => $filteredProjections,
                'messageCount' => $messageCount,
                'statuses' => array_map(fn (ProjectionStatus $status) => $status->value, ProjectionStatus::cases()),
                'modes' => array_map(fn (RunMode $mode) => $mode->value, RunMode::cases()),
                'groups' => array_keys($groups),
            ]),
        );
    }

    public function rebuildAction(string $id): Response
    {
        $criteria = new ProjectionistCriteria([$id]);

        $this->projectionist->remove($criteria);
        $this->projectionist->boot($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_projection_show'),
        );
    }

    public function pauseAction(string $id): Response
    {
        $criteria = new ProjectionistCriteria([$id]);

        $this->projectionist->pause($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_projection_show'),
        );
    }

    public function bootAction(string $id): Response
    {
        $criteria = new ProjectionistCriteria([$id]);

        $this->projectionist->boot($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_projection_show'),
        );
    }

    public function reactivateAction(string $id): Response
    {
        $criteria = new ProjectionistCriteria([$id]);

        $this->projectionist->reactivate($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_projection_show'),
        );
    }

    public function removeAction(string $id): Response
    {
        $criteria = new ProjectionistCriteria([$id]);

        $this->projectionist->remove($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_projection_show'),
        );
    }
}
