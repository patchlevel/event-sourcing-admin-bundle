<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Twig;

use Patchlevel\EventSourcingAdminBundle\Attribute\Inspect;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class InspectionExtension extends AbstractExtension
{
    /** @var array<class-string, Inspect> */
    private array $cache = [];

    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage,
    )
    {
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('eventsourcing_inspection_icon', $this->icon(...)),
            new TwigFunction('eventsourcing_inspection_description', $this->description(...), ['is_safe' => ['html']]),
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

        $template = $inspect->description ?: $default;

        if ($template === null) {
            return null;
        }

        $message = preg_replace_callback(
            '/\{\{ (.+) \}\}/U',
            fn($matches) => $this->expressionLanguage->evaluate($matches[1], [
                'event' => $event,
            ]),
            $template
        );

        return preg_replace('/\*\*([\w\s]+)\*\*/U', '<b>$1</b>', $message);
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
