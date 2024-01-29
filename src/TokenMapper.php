<?php

namespace Patchlevel\EventSourcingAdminBundle;

class TokenMapper
{
    /**
     * @var array<string, string>
     */
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

    public function get(string $requestId): ?string
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
        fputcsv($file, [$requestId, $debugToken]);
        fclose($file);
    }

    private function load(): array
    {
        $map = [];

        if (!file_exists($this->path())) {
            return $map;
        }

        $file = fopen($this->path(), 'r');

        while ($row = fgetcsv($file)) {
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
