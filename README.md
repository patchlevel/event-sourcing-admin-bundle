# Event-Sourcing-Bundle-Admin

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
patchlevel_event_sourcing_admin:
    resource: '@PatchlevelEventSourcingAdminBundle/Resources/config/routes.yaml'
```