# Production Usage

The dashboard was built as a development tool, but it can run in production as well. Because it
exposes internal data and lets visitors control your subscriptions, you must put it behind
authentication before enabling it outside of `dev`.

:::danger
The dashboard has no built in access control. Anyone who can reach it can read your events, see
internal information about your system and your users, and pause, rebuild or remove subscriptions.
Never expose it to the public without a security layer in front of it.
:::

## Install as a regular dependency

For production the bundle must be a normal dependency rather than a dev only one:

```bash
composer require patchlevel/event-sourcing-admin-bundle
```
Make sure the bundle is registered for the environments you want it in, for example by removing the
`['dev' => true]` restriction in `config/bundles.php`.

## Enable it for the right environments

Move the configuration out of the `when@dev` block so it applies in production too:

```yaml
# config/packages/patchlevel_event_sourcing_admin.yaml
patchlevel_event_sourcing_admin:
    enabled: true
```
Do the same for the routes import:

```yaml
# config/routes/patchlevel_event_sourcing_admin.yaml
event_sourcing:
    resource: '@PatchlevelEventSourcingAdminBundle/config/routes.yaml'
    prefix: /es-admin
```
## Secure the routes

Protect the prefix you mounted the dashboard under with the Symfony security component, for example
with an access control rule that requires an admin role:

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/es-admin, roles: ROLE_ADMIN }
```
:::warning
The subscription controls run synchronously in the web request. In production prefer the console
commands of the event-sourcing-bundle for heavy operations like full rebuilds, and keep the
dashboard for inspection and lighter actions.
:::
