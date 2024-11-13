<?php

declare(strict_types=1);

namespace Telephantast\Psr\Log;

use Psr\Log\AbstractLogger;

/**
 * @internal
 */
final class InMemoryLogger extends AbstractLogger
{
    /**
     * @var list<array{level: mixed, message: \Stringable|string, context: array}>
     */
    private array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    /**
     * @return list<array{level: mixed, message: \Stringable|string, context: array}>
     */
    public function popLogs(): array
    {
        $logs = $this->logs;
        $this->logs = [];

        return $logs;
    }
}
