<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Controller;

use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngineCriteria;
use Patchlevel\EventSourcing\Subscription\RunMode;
use Patchlevel\EventSourcing\Subscription\Status;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

use function array_keys;
use function array_map;
use function str_contains;

final class SubscriptionController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SubscriptionEngine $engine,
        private readonly Store $store,
        private readonly RouterInterface $router,
    ) {
    }

    public function showAction(Request $request): Response
    {
        $subscriptions = $this->engine->subscriptions();
        $messageCount = $this->store->count();

        $groups = [];

        foreach ($subscriptions as $subscription) {
            $groups[$subscription->group()] = true;
        }

        $filteredSubscriptions = [];
        $search = $request->query->getString('search');
        $group = $request->query->getString('group');
        $mode = $request->query->getString('mode');
        $status = $request->query->getString('status');

        foreach ($subscriptions as $subscription) {
            if ($search && !str_contains($subscription->id(), $search)) {
                continue;
            }

            if ($group && $subscription->group() !== $group) {
                continue;
            }

            if ($mode && $subscription->runMode()->value !== $mode) {
                continue;
            }

            if ($status && $subscription->status()->value !== $status) {
                continue;
            }

            $filteredSubscriptions[] = $subscription;
        }

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/subscription/show.html.twig', [
                'subscriptions' => $filteredSubscriptions,
                'messageCount' => $messageCount,
                'statuses' => array_map(static fn (Status $status) => $status->value, Status::cases()),
                'modes' => array_map(static fn (RunMode $mode) => $mode->value, RunMode::cases()),
                'groups' => array_keys($groups),
            ]),
        );
    }

    public function rebuildAction(string $id): Response
    {
        $criteria = new SubscriptionEngineCriteria([$id]);

        $this->engine->remove($criteria);
        $this->engine->boot($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_subscription_show'),
        );
    }

    public function pauseAction(string $id): Response
    {
        $criteria = new SubscriptionEngineCriteria([$id]);

        $this->engine->pause($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_subscription_show'),
        );
    }

    public function bootAction(Request $request): Response
    {
        $criteria = $this->criteria($request);
        $result = $this->engine->boot($criteria);

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/subscription/status.html.twig', [
                'type' => 'boot',
                'result' => $result,
                'criteria' => $criteria,
            ]),
        );
    }

    public function runAction(Request $request): Response
    {
        $criteria = $this->criteria($request);
        $result = $this->engine->run($criteria);

        return new Response(
            $this->twig->render('@PatchlevelEventSourcingAdmin/subscription/status.html.twig', [
                'type' => 'run',
                'criteria' => $criteria,
                'result' => $result,
            ]),
        );
    }

    public function setupAction(string $id): Response
    {
        $criteria = new SubscriptionEngineCriteria([$id]);

        $this->engine->setup($criteria, skipBooting: true);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_subscription_show'),
        );
    }

    public function reactivateAction(string $id): Response
    {
        $criteria = new SubscriptionEngineCriteria([$id]);

        $this->engine->reactivate($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_subscription_show'),
        );
    }

    public function removeAction(string $id): Response
    {
        $criteria = new SubscriptionEngineCriteria([$id]);

        $this->engine->remove($criteria);

        return new RedirectResponse(
            $this->router->generate('patchlevel_event_sourcing_admin_subscription_show'),
        );
    }

    private function criteria(Request $request): SubscriptionEngineCriteria
    {
        $id = $request->query->get('id');
        $ids = $id ? [$id] : null;

        $group = $request->query->get('group');
        $groups = $group ? [$group] : null;

        return new SubscriptionEngineCriteria($ids, $groups);
    }
}
