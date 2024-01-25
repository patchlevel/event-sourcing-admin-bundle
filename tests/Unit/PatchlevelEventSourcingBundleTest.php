<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Tests\Unit;

use Patchlevel\EventSourcingAdminBundle\Controller\DefaultController;
use Patchlevel\EventSourcingAdminBundle\Controller\ProjectionController;
use Patchlevel\EventSourcingAdminBundle\Controller\StoreController;
use Patchlevel\EventSourcingAdminBundle\DependencyInjection\PatchlevelEventSourcingAdminExtension;
use Patchlevel\EventSourcingAdminBundle\PatchlevelEventSourcingAdminBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PatchlevelEventSourcingBundleTest extends TestCase
{
    use ProphecyTrait;

    public function testEmptyConfig(): void
    {
        $container = new ContainerBuilder();
        $bundle = new PatchlevelEventSourcingAdminBundle();

        $bundle->build($container);

        $extension = new PatchlevelEventSourcingAdminExtension();
        $extension->load([], $container);

        $container->compile();

        self::assertFalse($container->has(DefaultController::class));
        self::assertFalse($container->has(ProjectionController::class));
        self::assertFalse($container->has(StoreController::class));
    }

    public function testEnabled(): void
    {
        $container = new ContainerBuilder();
        $this->compileContainer(
            $container,
            [
                'patchlevel_event_sourcing_admin' => [
                    'enabled' => true,
                ],
            ]
        );


        self::assertEquals(DefaultController::class, DefaultController::class);
        self::assertEquals(ProjectionController::class, ProjectionController::class);
        self::assertEquals(StoreController::class, StoreController::class);
    }

    private function compileContainer(ContainerBuilder $container, array $config): void
    {
        $bundle = new PatchlevelEventSourcingAdminBundle();
        $bundle->build($container);

        $container->setParameter('kernel.project_dir', __DIR__);

        // services

        $extension = new PatchlevelEventSourcingAdminExtension();
        $extension->load($config, $container);

        $compilerPassConfig = $container->getCompilerPassConfig();
        $compilerPassConfig->setRemovingPasses([]);
        $compilerPassConfig->addPass(new TestCaseAllPublicCompilerPass());

        $container->compile();
    }
}
