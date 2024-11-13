<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\CreatedAt;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Telephantast\MessageBus\MessageBus;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Pipeline;
use Telephantast\MessageBus\TestHandler;
use Telephantast\MessageBus\TestMessage;
use Telephantast\Psr\Clock\ConstClock;

/**
 * @internal
 */
#[CoversClass(className: AddCreatedAtMiddleware::class)]
#[CoversClass(className: CreatedAt::class)]
final class AddCreatedAtMiddlewareTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable();
    }

    public function testHandle(): void
    {
        $messageContext = $this->createMessageContext();

        Pipeline::handle($messageContext, $this->createHandler(), [$this->createMiddleware()]);

        self::assertEquals($this->now, $messageContext->getStamp(CreatedAt::class)?->time);
    }

    public function testHandleCreatedAtStampAlreadyExists(): void
    {
        $messageContext = $this->createMessageContext();

        $createdAt = new \DateTimeImmutable('-1 day');
        $messageContext->setStamp(new CreatedAt(time: $createdAt));

        Pipeline::handle($messageContext, $this->createHandler(), [$this->createMiddleware()]);

        self::assertEquals($createdAt, $messageContext->getStamp(CreatedAt::class)?->time);
    }

    /**
     * @return MessageContext<void, TestMessage>
     */
    private function createMessageContext(): MessageContext
    {
        return MessageContext::start(new MessageBus(), new TestMessage());
    }

    private function createHandler(): TestHandler
    {
        return new TestHandler();
    }

    private function createMiddleware(): Middleware
    {
        return new AddCreatedAtMiddleware(
            new ConstClock($this->now),
        );
    }
}
