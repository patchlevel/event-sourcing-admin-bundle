<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class DefaultController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/')]
    public function indexAction(): Response
    {
        return new RedirectResponse($this->router->generate('patchlevel_eventsourcingadmin_store_show'));
    }

    #[Route('/style.css')]
    public function styleAction(): Response
    {
        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/style.css.twig'),
            200,
            ['Content-Type' => 'text/css'],
        );
    }
}
