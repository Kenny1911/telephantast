<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler\CallableHandler;

/**
 * @internal
 */
#[CoversClass(className: Pipeline::class)]
final class PipelineTest extends TestCase
{
    /** @var MessageContext<list<string>, Message<list<string>>> */
    private MessageContext $messageContext;

    /** @var Handler<list<string>, Message<list<string>>> */
    private Handler $handler;

    private Middleware $middleware1;

    private Middleware $middleware2;

    protected function setUp(): void
    {
        /**
         * @var Message<list<string>> $message
         * @psalm-suppress MissingTemplateParam
         */
        $message = new class implements Message {};
        $this->messageContext = MessageContext::start(new MessageBus(), $message);

        $this->handler = new CallableHandler('test', static fn() => ['handler']);

        $this->middleware1 = new class implements Middleware {
            public function handle(MessageContext $messageContext, Pipeline $pipeline): array
            {
                return ['middleware1', ...(array) $pipeline->continue()];
            }
        };

        $this->middleware2 = new class implements Middleware {
            public function handle(MessageContext $messageContext, Pipeline $pipeline): array
            {
                return ['middleware2', ...(array) $pipeline->continue()];
            }
        };
    }

    public function testHandleNoMiddlewares(): void
    {
        self::assertSame(
            ['handler'],
            Pipeline::handle(
                messageContext: $this->messageContext,
                handler: $this->handler,
                middlewares: [],
            ),
        );
    }

    public function testHandleArrayMiddlewares(): void
    {
        self::assertSame(
            ['middleware1', 'middleware2', 'handler'],
            Pipeline::handle(
                messageContext: $this->messageContext,
                handler: $this->handler,
                middlewares: [$this->middleware1, $this->middleware2],
            ),
        );
    }

    public function testHandleIteratorMiddlewares(): void
    {
        self::assertSame(
            ['middleware1', 'middleware2', 'handler'],
            Pipeline::handle(
                messageContext: $this->messageContext,
                handler: $this->handler,
                middlewares: new \ArrayIterator([$this->middleware1, $this->middleware2]),
            ),
        );
    }

    public function testHandleGeneratorMiddlewares(): void
    {
        self::assertSame(
            ['middleware1', 'middleware2', 'handler'],
            Pipeline::handle(
                messageContext: $this->messageContext,
                handler: $this->handler,
                middlewares: (fn() => yield from [$this->middleware1, $this->middleware2])(),
            ),
        );
    }

    public function testHandleGeneratorMiddlewaresWithSameKeys(): void
    {
        self::assertSame(
            ['middleware1', 'middleware2', 'handler'],
            Pipeline::handle(
                messageContext: $this->messageContext,
                handler: $this->handler,
                middlewares: (function () {
                    yield 'key' => $this->middleware1;
                    yield 'key' => $this->middleware2;
                })(),
            ),
        );
    }

    public function testId(): void
    {
        $middleware = new class ($this) implements Middleware {
            public function __construct(private readonly PipelineTest $test) {}

            public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
            {
                $this->test::assertSame('test', $pipeline->id());

                return $pipeline->continue();
            }
        };

        Pipeline::handle(
            messageContext: $this->messageContext,
            handler: $this->handler,
            middlewares: [$middleware],
        );
    }

    public function testContinuePipelineFullyHandled(): void
    {
        $this->expectException(PipelineFullyHandled::class);

        $middleware = new class implements Middleware {
            public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
            {
                $pipeline->continue();

                return $pipeline->continue();
            }
        };

        Pipeline::handle(
            messageContext: $this->messageContext,
            handler: $this->handler,
            middlewares: [$middleware],
        );
    }
}
