[![Latest Stable Version](https://poser.pugx.org/patchlevel/event-sourcing-admin-bundle/v)](https://packagist.org/packages/patchlevel/event-sourcing-admin-bundle)
[![License](https://poser.pugx.org/patchlevel/event-sourcing-admin-bundle/license)](https://packagist.org/packages/patchlevel/event-sourcing-admin-bundle)

# Event-Sourcing-Admin-Bundle

"A dashboard to inspect your events, time travel through your aggregates and manage your subscriptions."

## Features

* Browse the raw event [store](https://patchlevel.dev/docs/event-sourcing-admin-bundle/latest/store) and filter by aggregate, id, stream or event
* [Inspect](https://patchlevel.dev/docs/event-sourcing-admin-bundle/latest/inspection) a single aggregate: its events, serialized state, snapshot and a full state dump
* Time travel through an aggregate to see its state at any [playhead](https://patchlevel.dev/docs/event-sourcing-admin-bundle/latest/inspection)
* List all registered [events](https://patchlevel.dev/docs/event-sourcing-admin-bundle/latest/events) together with their listeners and subscribers
* View and control [subscriptions](https://patchlevel.dev/docs/event-sourcing-admin-bundle/latest/subscriptions): boot, run, pause, reactivate, rebuild or remove
* [Customize](https://patchlevel.dev/docs/event-sourcing-admin-bundle/latest/events) how events are rendered with the `#[Inspect]` attribute
* and much more...

## Installation

```bash
composer require --dev patchlevel/event-sourcing-admin-bundle
```

## Documentation

* Latest [Docs](https://patchlevel.dev/docs/event-sourcing-admin-bundle/latest)
* Related [Blog](https://patchlevel.dev/blog)

## Screenshots

### Store

![Screenshot1](docs/screenshot1.png)

### Inspector

![Screenshot2](docs/screenshot2.png)

### Subscriptions

![Screenshot3](docs/screenshot3.png)

### Events

![Screenshot4](docs/screenshot4.png)

## Integration

* [event-sourcing](https://github.com/patchlevel/event-sourcing)
* [event-sourcing-bundle](https://github.com/patchlevel/event-sourcing-bundle)

## Contributing

We are open to contributions as long as they are in line with
our [BC-Policy](https://patchlevel.dev/our-backward-compatibility-promise).

Also note that the `composer.lock` is always generated with the newest supported PHP version as this is the version our tools run in the CI.
