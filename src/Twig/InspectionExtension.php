<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Twig;

use Patchlevel\EventSourcingAdminBundle\Attribute\Inspect;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class InspectionExtension extends AbstractExtension
{
    /** @var array<class-string, Inspect> */
    private array $cache = [];

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('eventsourcing_inspection_icon', $this->icon(...)),
            new TwigFunction('eventsourcing_inspection_description', $this->description(...)),
            new TwigFunction('eventsourcing_inspection_color', $this->color(...)),
        ];
    }

    private function icon(object $event, string|null $default = null): string|null
    {
        $inspect = $this->inspect($event);

        return $inspect->icon ?: $default;
    }

    private function description(object $event, string|null $default = null): string|null
    {
        $inspect = $this->inspect($event);

        return $inspect->description ?: $default;
    }

    private function color(object $event, string|null $default = null): string|null
    {
        $inspect = $this->inspect($event);

        return $inspect->color ?: $default;
    }

    private function inspect(object $event): Inspect
    {
        if (isset($this->cache[$event::class])) {
            return $this->cache[$event::class];
        }

        $reflection = new \ReflectionClass($event);
        $attributes = $reflection->getAttributes(Inspect::class);

        if (count($attributes) === 0) {
            return new Inspect();
        }

        $this->cache[$event::class] = $attributes[0]->newInstance();

        return $this->cache[$event::class];
    }
}
