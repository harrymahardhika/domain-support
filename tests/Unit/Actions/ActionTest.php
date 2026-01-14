<?php

declare(strict_types=1);

use HarryM\DomainSupport\Actions\AbstractAction;
use HarryM\DomainSupport\Actions\AbstractAsyncAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

// Test implementations for AbstractAction
class TestSyncAction extends AbstractAction
{
    public function __construct(
        public string $message = 'test message',
        public bool $shouldFail = false
    ) {}

    #[\Override]
    public function handle(): string
    {
        throw_if($this->shouldFail, Exception::class, 'Action failed');

        return 'Handled: '.$this->message;
    }
}

class TestSyncActionWithoutReturn extends AbstractAction
{
    public bool $executed = false;

    #[\Override]
    public function handle(): void
    {
        $this->executed = true;
    }
}

// Test implementations for AbstractAsyncAction
class TestAsyncAction extends AbstractAsyncAction
{
    public function __construct(
        public string $message = 'async test message',
        public bool $shouldFail = false
    ) {}

    #[\Override]
    public function handle(): string
    {
        throw_if($this->shouldFail, Exception::class, 'Async action failed');

        return 'Async handled: '.$this->message;
    }
}

class TestAsyncActionWithoutReturn extends AbstractAsyncAction
{
    public function __construct(public string $data = 'test data') {}

    #[\Override]
    public function handle(): void
    {
        // Simulate some work
        file_put_contents(storage_path('test_async_executed.txt'), $this->data);
    }
}

describe('AbstractAction', function (): void {
    describe('Class structure', function (): void {
        it('uses Dispatchable trait', function (): void {
            new TestSyncAction();

            expect(class_uses_recursive(TestSyncAction::class))
                ->toContain(Dispatchable::class);
        });

        it('has abstract handle method', function (): void {
            $reflection = new ReflectionClass(AbstractAction::class);
            $handleMethod = $reflection->getMethod('handle');

            expect($handleMethod->isAbstract())->toBeTrue();
        });

        it('can be instantiated via concrete implementation', function (): void {
            $action = new TestSyncAction('hello world');

            expect($action)->toBeInstanceOf(AbstractAction::class);
            expect($action->message)->toBe('hello world');
        });
    });

    describe('Direct execution', function (): void {
        it('can execute handle method directly', function (): void {
            $action = new TestSyncAction('direct execution');
            $result = $action->handle();

            expect($result)->toBe('Handled: direct execution');
        });

        it('can handle actions without return values', function (): void {
            $action = new TestSyncActionWithoutReturn();
            $action->handle();

            expect($action->executed)->toBeTrue();
        });

        it('can handle exceptions in action', function (): void {
            $action = new TestSyncAction('fail test', true);

            expect(fn (): string => $action->handle())
                ->toThrow(Exception::class, 'Action failed');
        });
    });

    describe('Dispatch functionality', function (): void {
        it('can be dispatched synchronously', function (): void {
            Bus::fake();

            dispatch(new \TestSyncAction('dispatched message'));

            Bus::assertDispatched(TestSyncAction::class, fn ($action): bool => 'dispatched message' === $action->message);
        });

        it('can be dispatched with multiple parameters', function (): void {
            Bus::fake();

            dispatch(new \TestSyncAction('test', false));

            Bus::assertDispatched(TestSyncAction::class, fn ($action): bool => 'test' === $action->message && false === $action->shouldFail);
        });

        it('can dispatch and execute immediately', function (): void {
            $result = dispatch_sync(new \TestSyncAction('sync dispatch'));

            expect($result)->toBe('Handled: sync dispatch');
        });
    });
});

describe('AbstractAsyncAction', function (): void {
    describe('Class structure', function (): void {
        it('implements ShouldQueue interface', function (): void {
            expect(TestAsyncAction::class)
                ->toImplement(ShouldQueue::class);
        });

        it('uses required traits for queue functionality', function (): void {
            $traits = class_uses_recursive(TestAsyncAction::class);

            expect($traits)->toContain(Dispatchable::class);
            expect($traits)->toContain(InteractsWithQueue::class);
            expect($traits)->toContain(Queueable::class);
            expect($traits)->toContain(SerializesModels::class);
        });

        it('inherits abstract handle method', function (): void {
            $reflection = new ReflectionClass(AbstractAsyncAction::class);
            $handleMethod = $reflection->getMethod('handle');

            expect($handleMethod->isAbstract())->toBeTrue();
        });

        it('can be instantiated via concrete implementation', function (): void {
            $action = new TestAsyncAction('async hello');

            expect($action)->toBeInstanceOf(AbstractAsyncAction::class);
            expect($action->message)->toBe('async hello');
        });
    });

    describe('Direct execution', function (): void {
        it('can execute handle method directly', function (): void {
            $action = new TestAsyncAction('direct async');
            $result = $action->handle();

            expect($result)->toBe('Async handled: direct async');
        });

        it('can handle async actions without return values', function (): void {
            $action = new TestAsyncActionWithoutReturn('test data');
            $action->handle();

            expect(file_get_contents(storage_path('test_async_executed.txt')))
                ->toBe('test data');

            // Cleanup
            unlink(storage_path('test_async_executed.txt'));
        });

        it('can handle exceptions in async action', function (): void {
            $action = new TestAsyncAction('async fail', true);

            expect(fn (): string => $action->handle())
                ->toThrow(Exception::class, 'Async action failed');
        });
    });

    describe('Queue dispatch functionality', function (): void {
        beforeEach(function (): void {
            Queue::fake();
        });

        it('can be dispatched to queue', function (): void {
            dispatch(new \TestAsyncAction('queued message'));

            Queue::assertPushed(TestAsyncAction::class, fn ($action): bool => 'queued message' === $action->message);
        });

        it('can be dispatched with delay', function (): void {
            dispatch(new \TestAsyncAction('delayed message'))->delay(now()->addMinutes(5));

            Queue::assertPushed(TestAsyncAction::class, fn ($action): bool => 'delayed message' === $action->message);
        });

        it('can be dispatched to specific queue', function (): void {
            dispatch(new \TestAsyncAction('queue specific'))->onQueue('high-priority');

            Queue::assertPushedOn('high-priority', TestAsyncAction::class);
        });

        it('can be dispatched with connection', function (): void {
            dispatch(new \TestAsyncAction('connection test'))->onConnection('redis');

            Queue::assertPushed(TestAsyncAction::class, fn ($action): bool => 'redis' === $action->connection);
        });

        it('can dispatch multiple jobs', function (): void {
            dispatch(new \TestAsyncAction('job 1'));
            dispatch(new \TestAsyncAction('job 2'));
            dispatch(new \TestAsyncAction('job 3'));

            Queue::assertPushed(TestAsyncAction::class, 3);
        });
    });
});

describe('Action comparison', function (): void {
    it('sync action does not implement ShouldQueue', function (): void {
        expect(TestSyncAction::class)
            ->not->toImplement(ShouldQueue::class);
    });

    it('async action implements ShouldQueue', function (): void {
        expect(TestAsyncAction::class)
            ->toImplement(ShouldQueue::class);
    });

    it('both actions use Dispatchable trait', function (): void {
        expect(class_uses_recursive(TestSyncAction::class))
            ->toContain(Dispatchable::class);
        expect(class_uses_recursive(TestAsyncAction::class))
            ->toContain(Dispatchable::class);
    });

    it('only async action uses queue-related traits', function (): void {
        $syncTraits = class_uses_recursive(TestSyncAction::class);
        $asyncTraits = class_uses_recursive(TestAsyncAction::class);

        expect($syncTraits)->not->toContain(Queueable::class);
        expect($syncTraits)->not->toContain(InteractsWithQueue::class);
        expect($syncTraits)->not->toContain(SerializesModels::class);

        expect($asyncTraits)->toContain(Queueable::class);
        expect($asyncTraits)->toContain(InteractsWithQueue::class);
        expect($asyncTraits)->toContain(SerializesModels::class);
    });
});

describe('Integration scenarios', function (): void {
    it('can chain multiple sync actions', function (): void {
        Bus::fake();

        dispatch(new \TestSyncAction('action 1'));
        dispatch(new \TestSyncAction('action 2'));

        Bus::assertDispatched(TestSyncAction::class, 2);
    });

    // it('can mix sync and async action dispatches', function () {
    //     Bus::fake();
    //     Queue::fake();
    //
    //     TestSyncAction::dispatch('sync action');
    //     TestAsyncAction::dispatch('async action');
    //
    //     Bus::assertDispatched(TestSyncAction::class);
    //     Queue::assertPushed(TestAsyncAction::class);
    // });

    it('can handle serialization for async actions', function (): void {
        $action = new TestAsyncAction('serialization test');
        $serialized = serialize($action);
        $unserialized = unserialize($serialized);

        expect($unserialized->message)->toBe('serialization test');
        expect($unserialized->handle())->toBe('Async handled: serialization test');
    });
});

describe('Error handling', function (): void {
    it('handles sync action failures immediately', function (): void {
        expect(fn () => dispatch_sync(new \TestSyncAction('test', true)))
            ->toThrow(Exception::class, 'Action failed');
    });

    it('queues async action even if it might fail', function (): void {
        Queue::fake();

        dispatch(new \TestAsyncAction('fail test', true));

        Queue::assertPushed(TestAsyncAction::class, fn ($action): bool => true === $action->shouldFail);
    });
});
