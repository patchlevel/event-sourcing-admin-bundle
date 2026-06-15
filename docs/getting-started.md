# Getting Started

This guide enables the admin bundle in an existing Symfony application that already uses the
[event-sourcing-bundle](https://github.com/patchlevel/event-sourcing-bundle), and walks you through
opening the dashboard for the first time. The examples use a small hotel domain, with a `Hotel`
aggregate that records events like `GuestIsCheckedIn` and `GuestIsCheckedOut`.

:::note
The admin bundle reads its data from the services configured by the event-sourcing-bundle (the
store, the aggregate and event registries, and the subscription engine). If your application is not
set up yet, start with the event-sourcing
[getting started](https://patchlevel.dev/docs/event-sourcing/latest) guide first.
:::

## Install the bundle

Require the package as a development dependency:

```bash
composer require --dev patchlevel/event-sourcing-admin-bundle
```
If you use Symfony Flex without auto-registration, register the bundle for the `dev` environment in
`config/bundles.php`:

```php
use Patchlevel\EventSourcingAdminBundle\PatchlevelEventSourcingAdminBundle;

return [
    // ...
    PatchlevelEventSourcingAdminBundle::class => ['dev' => true],
];
```
## Enable the bundle

The bundle does nothing until you set `enabled` to `true`. Enable it only for the `dev`
environment:

```yaml
# config/packages/patchlevel_event_sourcing_admin.yaml
when@dev:
    patchlevel_event_sourcing_admin:
        enabled: true
```
:::warning
When `enabled` is `false`, none of the controllers, routes or services are registered. This is the
default, so the dashboard stays off until you opt in.
:::

## Register the routes

The dashboard ships its own routing file. Import it under a prefix of your choice, again scoped to
`dev`:

```yaml
# config/routes/patchlevel_event_sourcing_admin.yaml
when@dev:
    event_sourcing:
        resource: '@PatchlevelEventSourcingAdminBundle/config/routes.yaml'
        prefix: /es-admin
```
## Build the assets

The dashboard ships compiled CSS and JavaScript as bundle assets. Install them into your public
directory:

```bash
bin/console assets:install
```
:::note
If you use the [asset mapper](https://symfony.com/doc/current/frontend/asset_mapper.html), the
bundle assets are picked up automatically. The bundle depends on `symfony/asset` and
`symfony/asset-mapper` for this.
:::

## Open the dashboard

Start your application and visit the prefix you chose:

```
https://localhost/es-admin/
```
The index route redirects to the [store](store.md), where you can see the latest recorded events.
From there you can jump into the [inspection](inspection.md) view of a single hotel, browse all
registered [events](events.md), or manage your [subscriptions](subscriptions.md).

## Result

You now have a working dashboard scoped to your `dev` environment. You can browse the event store,
inspect aggregates and time travel through their history, and control the subscription engine
without leaving the browser.

## Learn more

* [How to browse the event store](store.md)
* [How to inspect an aggregate and time travel](inspection.md)
* [How to manage subscriptions](subscriptions.md)
* [How to run the dashboard in production](production-usage.md)
