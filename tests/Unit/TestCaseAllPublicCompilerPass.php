<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestCaseAllPublicCompilerPass implements CompilerPassInterface
{
    private const SERVICE_PREFIX = 'event_sourcing_admin.';
    private const NAMESPACE_PREFIX = 'Patchlevel\\EventSourcingAdmin';

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($this->isOwnService($id)) {
                $definition->setPublic(true);
            }
        }

        foreach ($container->getAliases() as $id => $alias) {
            if ($this->isOwnService($id)) {
                $alias->setPublic(true);
            }
        }
    }

    private function isOwnService(string $id): bool
    {
        if (str_starts_with($id, self::SERVICE_PREFIX)) {
            return true;
        }

        return str_starts_with($id, self::NAMESPACE_PREFIX);
    }
}
