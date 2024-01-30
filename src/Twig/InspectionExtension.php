<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Twig;

use Patchlevel\EventSourcingAdminBundle\Attribute\Inspect;
use Patchlevel\EventSourcingAdminBundle\Color;
use ReflectionClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function count;
use function preg_replace;
use function preg_replace_callback;

final class InspectionExtension extends AbstractExtension
{
    /** @var array<class-string, Inspect> */
    private array $cache = [];

    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage,
    ) {
    }

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('eventsourcing_inspection_icon', $this->icon(...)),
            new TwigFunction('eventsourcing_inspection_color', $this->color(...)),
            new TwigFunction('eventsourcing_inspection_description', $this->description(...), ['is_safe' => ['html']]),
            new TwigFunction('eventsourcing_inspection_description_raw', $this->descriptionRaw(...)),
        ];
    }

    /**
     * @param class-string|object $event
     */
    private function icon(object|string $event, string|null $default = null): string|null
    {
        $inspect = $this->inspect($event);

        return $inspect->icon ?: $default;
    }

    /**
     * @param class-string|object $event
     */
    private function color(object|string $event, string|Color|null $default = null): string|null
    {
        $inspect = $this->inspect($event);

        $result = $inspect->color ?: $default;

        if ($result instanceof Color) {
            return $result->value;
        }

        return $result;
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
            fn ($matches) => $this->expressionLanguage->evaluate($matches[1], ['event' => $event]),
            $template,
        );

        return preg_replace(
            [
                '/\*\*([\S\s]+)\*\*/U', // **foo** -> <b>foo</b>
                '/\*([\S\s]+)\*/', // *foo* -> <i>foo</i>
            ],
            [
                '<b>$1</b>',
                '<i>$1</i>',
            ],
            $message,
        );
    }

    /**
     * @param class-string|object $event
     */
    private function descriptionRaw(object|string $event, string|null $default = null): string|null
    {
        $inspect = $this->inspect($event);

        return $inspect->description ?: $default;
    }

    /**
     * @param class-string|object $event
     */
    private function inspect(object|string $event): Inspect
    {
        if (is_object($event)) {
            $event = $event::class;
        }

        if (isset($this->cache[$event])) {
            return $this->cache[$event];
        }

        $reflection = new ReflectionClass($event);
        $attributes = $reflection->getAttributes(Inspect::class);

        if (count($attributes) === 0) {
            return new Inspect();
        }

        $this->cache[$event] = $attributes[0]->newInstance();

        return $this->cache[$event];
    }
}
