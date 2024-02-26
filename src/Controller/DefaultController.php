<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final class DefaultController
{
    public function __construct(
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
