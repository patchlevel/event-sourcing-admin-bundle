<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingAdminBundle\Message\Header;

use Patchlevel\EventSourcing\Attribute\Header;

/** @immutable */
#[Header(name: 'requestId')]
final class RequestIdHeader
{
    public function __construct(
        public readonly string $requestId,
    ) {
    }
}
