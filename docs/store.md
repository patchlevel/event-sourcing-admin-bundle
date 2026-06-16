# Store

The store view is the landing page of the dashboard. It lists the messages in your event store in
reverse order, newest first, so you can see what your application recorded most recently. The index
route of the bundle redirects here.

![The store view](screenshot1.png)

## Browsing events

Each row shows one recorded event together with its aggregate, id and playhead. The list is paged,
with 50 events per page by default, and you can page through the full history.

## Filtering

You can narrow the list down with several filters, which map directly to the store criteria:

* **aggregate**: only events of a given aggregate name, for example `hotel`
* **aggregate id**: only events for a single aggregate id
* **stream name**: only events of a specific stream
* **event**: only a single event type, for example `guest_is_checked_in`

The filters combine, so you can look at every `guest_is_checked_in` event of one hotel at once. The
available aggregate names and event names come from the aggregate and event registries, so the
filter dropdowns always reflect what is actually registered in your application.

:::tip
From the store you can jump straight into the [inspection](inspection.md) view of any aggregate to
see its full history and current state.
:::

## Learn more

* [How to inspect a single aggregate](inspection.md)
* [How to see which subscribers react to an event and customize its display](events.md)
* [How to manage subscriptions](subscriptions.md)
