<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Twig;

use RuntimeException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function file_exists;
use function file_get_contents;
use function sprintf;
use function str_replace;

final class HeroiconsExtension extends AbstractExtension
{
    private array $cache = [];

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [new TwigFunction('heroicon', $this->heroicon(...), ['is_safe' => ['html']])];
    }

    private function heroicon(string $icon, string|null $class = null): string
    {
        if (isset($this->cache[$icon])) {
            return $this->injectClass($this->cache[$icon], $class);
        }

        $path = $this->iconPath($icon);

        if (!file_exists($path)) {
            throw new RuntimeException(sprintf('heroicon "%s" not found', $icon));
        }

        $this->cache[$icon] = file_get_contents($path);

        return $this->injectClass($this->cache[$icon], $class);
    }

    private function iconPath(string $icon): string
    {
        return sprintf('%s/%s.svg', $this->iconBasePath(), $icon);
    }

    private function iconBasePath(): string
    {
        return __DIR__ . '/../../icons';
    }

    private function injectClass(string $svg, string|null $class = null): string
    {
        if ($class === null) {
            return $svg;
        }

        return str_replace('<svg ', sprintf('<svg class="%s" ', $class), $svg);
    }
}
