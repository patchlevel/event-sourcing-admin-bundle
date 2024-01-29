<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class EventController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EventRegistry $eventRegistry,
        private readonly RouterInterface $router,
    ) {
    }

    public function indexAction(): Response
    {
        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_store_show'),
        );
    }
}
