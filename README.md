# Event-Sourcing-Bundle-Admin

This bundle provides a simple admin interface for the [patchlevel/event-sourcing-bundle](https://github.com/patchlevel/event-sourcing-bundle).

## Screenshots

### Store

![Screenshot1](docs/screenshot1.png)

### Inspector

![Screenshot2](docs/screenshot2.png)

### Projection

![Screenshot3](docs/screenshot3.png)

### Events

![Screenshot4](docs/screenshot4.png)

## Installation

```bash
composer require patchlevel/event-sourcing-bundle
```

## Configuration

```yaml
# config/packages/patchlevel_event_sourcing_admin.yaml
patchlevel_event_sourcing_admin:
    enabled: true
```

## Routes

```yaml
# config/routes/patchlevel_event_sourcing_admin.yaml
event_sourcing:
  resource: '@PatchlevelEventSourcingAdminBundle/config/routes.yaml'
  prefix: /es-admin
```
