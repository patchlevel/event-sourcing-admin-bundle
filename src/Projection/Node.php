<?php

namespace Patchlevel\EventSourcingAdminBundle\Projection;

use JsonSerializable;

final class Node implements JsonSerializable
{
    public readonly string $id;

    public function __construct(
        public readonly string $name,
        public readonly string $category,
    ) {
        $this->id = sha1($category . '#' . $name);
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
        ];
    }
}
