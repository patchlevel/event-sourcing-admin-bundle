# Introduction

The Event-Sourcing Admin Bundle provides a web dashboard for applications built on
[patchlevel/event-sourcing](https://github.com/patchlevel/event-sourcing) and the
[event-sourcing-bundle](https://github.com/patchlevel/event-sourcing-bundle). It lets you browse the
event store, inspect aggregates over time, see how events connect to your subscribers, and manage
the subscription engine, all from the browser.

It was designed as a developer experience tool for local development, but it can also run in
production once it is placed behind proper authentication.

## Features

* Browse the raw event [store](store.md) and filter by aggregate, id, stream or event
* [Inspect](inspection.md) a single aggregate: its events, serialized state, snapshot and a full state dump
* Time travel through an aggregate to see its state at any [playhead](inspection.md)
* List all registered [events](events.md) together with their listeners and subscribers
* View and control [subscriptions](subscriptions.md): boot, run, pause, reactivate, rebuild or remove
* [Customize](events.md) how events are rendered with the `#[Inspect]` attribute

## Installation

```bash
composer require --dev patchlevel/event-sourcing-admin-bundle
```
:::tip
Follow the [getting started](getting-started.md) guide to enable the bundle, register its routes
and open the dashboard for the first time.
:::
