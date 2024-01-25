<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle;

use Patchlevel\EventSourcingAdminBundle\DependencyInjection\PatchlevelEventSourcingAdminExtension;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class PatchlevelEventSourcingAdminBundle extends AbstractBundle
{
    public function getContainerExtension(): PatchlevelEventSourcingAdminExtension
    {
        return new PatchlevelEventSourcingAdminExtension();
    }
}
