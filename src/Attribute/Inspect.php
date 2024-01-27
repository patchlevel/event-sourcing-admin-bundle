<?php

namespace Patchlevel\EventSourcingAdminBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Inspect
{
    public function __construct(
        public readonly string|null $description = null,
        public readonly string|null $icon = null,
        public readonly string|null $color = null,
        public readonly string|null $size = null,
    )
    {
    }
}
