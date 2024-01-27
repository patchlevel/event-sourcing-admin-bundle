<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\DependencyInjection;

use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Projection\Projectionist\Projectionist;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcingAdminBundle\Controller\DefaultController;
use Patchlevel\EventSourcingAdminBundle\Controller\InspectionController;
use Patchlevel\EventSourcingAdminBundle\Controller\ProjectionController;
use Patchlevel\EventSourcingAdminBundle\Controller\StoreController;
use Patchlevel\EventSourcingAdminBundle\Twig\EventSourcingAdminExtension;
use Patchlevel\EventSourcingAdminBundle\Twig\HeroiconsExtension;
use Patchlevel\EventSourcingAdminBundle\Twig\InspectionExtension;
use Patchlevel\Hydrator\Hydrator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Routing\RouterInterface;

/**
 * @psalm-type Config = array{
 *     enabled: bool
 * }
 */
final class PatchlevelEventSourcingAdminExtension extends Extension
{
    /** @param array<array-key, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        /** @var Config $config */
        $config = $this->processConfiguration($configuration, $configs);

        if (!$config['enabled']) {
            return;
        }

        $container->register(DefaultController::class)
            ->setArguments([
                new Reference('twig'),
                new Reference(RouterInterface::class),
            ])
            ->addTag('controller.service_arguments');

        $container->register(StoreController::class)
            ->setArguments([
                new Reference('twig'),
                new Reference(Store::class),
                new Reference(AggregateRootRegistry::class),
            ])
            ->addTag('controller.service_arguments');

        $container->register(InspectionController::class)
            ->setArguments([
                new Reference('twig'),
                new Reference(Store::class),
                new Reference(RepositoryManager::class),
                new Reference(AggregateRootRegistry::class),
                new Reference(Hydrator::class),
            ])
            ->addTag('controller.service_arguments');

        $container->register(ProjectionController::class)
            ->setArguments([
                new Reference('twig'),
                new Reference(Projectionist::class),
                new Reference(Store::class),
                new Reference(RouterInterface::class),
            ])
            ->addTag('controller.service_arguments');

        $container->register(EventSourcingAdminExtension::class)
            ->setArguments([
                new Reference(AggregateRootRegistry::class),
                new Reference(EventRegistry::class),
                new Reference(EventSerializer::class),
            ])
            ->addTag('twig.extension');

        $container->register(HeroiconsExtension::class)
            ->addTag('twig.extension');

        $container->register('event_sourcing_admin.expression_language', ExpressionLanguage::class);

        $container->register(InspectionExtension::class)
            ->setArguments([
                new Reference('event_sourcing_admin.expression_language'),
            ])
            ->addTag('twig.extension');
    }
}
