<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\DependencyInjection;

use Patchlevel\EventSourcing\EventBus\ListenerProvider;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootMetadataFactory;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Metadata\Projector\ProjectorMetadataFactory;
use Patchlevel\EventSourcing\Projection\Projectionist\Projectionist;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\EventSourcing\Snapshot\SnapshotStore;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcingAdminBundle\Controller\DefaultController;
use Patchlevel\EventSourcingAdminBundle\Controller\EventController;
use Patchlevel\EventSourcingAdminBundle\Controller\InspectionController;
use Patchlevel\EventSourcingAdminBundle\Controller\ProjectionController;
use Patchlevel\EventSourcingAdminBundle\Controller\StoreController;
use Patchlevel\EventSourcingAdminBundle\Decorator\RequestIdDecorator;
use Patchlevel\EventSourcingAdminBundle\Listener\RequestIdListener;
use Patchlevel\EventSourcingAdminBundle\Listener\TokenMapperListener;
use Patchlevel\EventSourcingAdminBundle\TokenMapper;
use Patchlevel\EventSourcingAdminBundle\Twig\EventSourcingAdminExtension;
use Patchlevel\EventSourcingAdminBundle\Twig\HeroiconsExtension;
use Patchlevel\EventSourcingAdminBundle\Twig\InspectionExtension;
use Patchlevel\Hydrator\Hydrator;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
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
                new Reference(RouterInterface::class),
                new Reference(Store::class),
                new Reference(AggregateRootRegistry::class),
                new Reference(AggregateRootMetadataFactory::class),
                new Reference(Hydrator::class),
                new Reference(SnapshotStore::class),
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

        $container->register(EventController::class)
            ->setArguments([
                new Reference('twig'),
                new Reference(EventRegistry::class),
                new Reference(ListenerProvider::class),
                new TaggedIteratorArgument('event_sourcing.projector'),
                new Reference(ProjectorMetadataFactory::class),
            ])
            ->addTag('controller.service_arguments');

        $container->register(EventSourcingAdminExtension::class)
            ->setArguments([
                new Reference(AggregateRootRegistry::class),
                new Reference(EventRegistry::class),
                new Reference(EventSerializer::class),
                new Reference(TokenMapper::class),
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

        $container->register(RequestIdDecorator::class)
            ->setArguments([
                new Reference('request_stack'),
            ])
            ->addTag('event_sourcing.message_decorator');

        $container->register(RequestIdListener::class)
            ->addTag('kernel.event_listener', [
                'event' => 'kernel.request',
                'method' => '__invoke',
                'priority' => 200,
            ]);

        $container->register(TokenMapper::class)
            ->setArguments([
                new Parameter('kernel.cache_dir'),
            ]);

        $container->register(TokenMapperListener::class)
            ->setArguments([
                new Reference(TokenMapper::class),
            ])
            ->addTag('kernel.event_listener', [
                'event' => 'kernel.response',
                'method' => '__invoke',
                'priority' => -200,
            ]);
    }
}
