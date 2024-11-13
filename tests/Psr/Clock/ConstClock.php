<?php

declare(strict_types=1);

namespace Telephantast\Psr\Clock;

use Psr\Clock\ClockInterface;

/**
 * @internal
 */
final class ConstClock implements ClockInterface
{
    public function __construct(
        private readonly \DateTimeImmutable $now,
    ) {}

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
