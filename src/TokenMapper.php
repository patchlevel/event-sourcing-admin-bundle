<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle;

use RuntimeException;

use function fclose;
use function fgetcsv;
use function file_exists;
use function fopen;
use function fputcsv;
use function sprintf;
use function touch;

final class TokenMapper
{
    /** @var array<string, string> */
    private array $map = [];

    public function __construct(private readonly string $path)
    {
    }

    public function set(string $requestId, string $debugToken): void
    {
        if ($this->map === []) {
            $this->map = $this->load();
        }

        $this->map[$requestId] = $debugToken;
        $this->write($requestId, $debugToken);
    }

    public function get(string $requestId): string|null
    {
        if ($this->map === []) {
            $this->map = $this->load();
        }

        return $this->map[$requestId] ?? null;
    }

    private function write(string $requestId, string $debugToken): void
    {
        if (!file_exists($this->path())) {
            touch($this->path());
        }

        $file = fopen($this->path(), 'a+');

        if ($file === false) {
            throw new RuntimeException(sprintf('File [%s] not found', $this->path()));
        }

        fputcsv($file, [$requestId, $debugToken]);
        fclose($file);
    }

    /** @return array<string, string> */
    private function load(): array
    {
        $map = [];

        if (!file_exists($this->path())) {
            return $map;
        }

        $file = fopen($this->path(), 'r');

        if ($file === false) {
            throw new RuntimeException(sprintf('File [%s] not found', $this->path()));
        }

        while ($row = fgetcsv($file)) {
            /** @var array{0: string, 1: string} $row */
            $map[$row[0]] = $row[1];
        }

        fclose($file);

        return $map;
    }

    private function path(): string
    {
        return $this->path . '/request_debug_token_map.csv';
    }
}
