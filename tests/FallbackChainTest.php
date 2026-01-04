<?php

declare(strict_types=1);

namespace MahdiMh\FallbackChain\Tests;

use Exception;
use MahdiMh\FallbackChain\AllStagesFailedException;
use MahdiMh\FallbackChain\FallbackChain;
use MahdiMh\FallbackChain\Stage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FallbackChainTest extends TestCase
{
    public function testCanCreateChainWithContext(): void
    {
        $context = ['key' => 'value'];

        $chain = new FallbackChain($context);

        $this->assertInstanceOf(FallbackChain::class, $chain);
    }

    public function testCanCreateChainWithoutContext(): void
    {
        $chain = new FallbackChain();

        $this->assertInstanceOf(FallbackChain::class, $chain);
    }

    public function testNullContextIsPassedToHandlers(): void
    {
        $receivedContext = 'not-null';
        $chain = new FallbackChain();

        $chain->add(Stage::handler(function ($ctx) use (&$receivedContext) {
            $receivedContext = $ctx;
            return 'done';
        }));

        $chain->execute();

        $this->assertNull($receivedContext);
    }

    public function testNullContextIsPassedToOnFailureCallbacks(): void
    {
        $receivedContext = 'not-null';
        $chain = new FallbackChain();

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('Stage failed');
            })->onFailure(function ($e, $ctx) use (&$receivedContext) {
                $receivedContext = $ctx;
            }))
            ->add(Stage::handler(function ($ctx) {
                return 'fallback succeeded';
            }));

        $chain->execute();

        $this->assertNull($receivedContext);
    }

    public function testAddReturnsFluentInterface(): void
    {
        $chain = new FallbackChain([]);
        $stage = Stage::handler(function ($ctx) {
            return 'result';
        });

        $result = $chain->add($stage);

        $this->assertSame($chain, $result);
    }

    public function testExecuteReturnsSingleStageResult(): void
    {
        $chain = new FallbackChain(['message' => 'hello']);

        $chain->add(Stage::handler(function ($ctx) {
            return $ctx['message'] . ' world';
        }));

        $result = $chain->execute();

        $this->assertSame('hello world', $result);
    }

    public function testExecuteReturnsFirstSuccessfulStageResult(): void
    {
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                return 'first';
            }))
            ->add(Stage::handler(function ($ctx) {
                return 'second';
            }));

        $result = $chain->execute();

        $this->assertSame('first', $result);
    }

    public function testExecuteContinuesToNextStageOnFailure(): void
    {
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('First failed');
            }))
            ->add(Stage::handler(function ($ctx) {
                return 'second succeeded';
            }));

        $result = $chain->execute();

        $this->assertSame('second succeeded', $result);
    }

    public function testExecutePassesContextToHandlers(): void
    {
        $context = [
            'to' => '09121234567',
            'text' => 'Hello',
        ];

        $receivedContext = null;
        $chain = new FallbackChain($context);

        $chain->add(Stage::handler(function ($ctx) use (&$receivedContext) {
            $receivedContext = $ctx;
            return 'done';
        }));

        $chain->execute();

        $this->assertSame($context, $receivedContext);
    }

    public function testExecuteThrowsExceptionWhenAllStagesFail(): void
    {
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('First failed');
            }))
            ->add(Stage::handler(function ($ctx) {
                throw new RuntimeException('Second failed');
            }));

        $this->expectException(AllStagesFailedException::class);
        $this->expectExceptionMessage('All stages in the fallback chain have failed.');

        $chain->execute();
    }

    public function testAllStagesFailedExceptionContainsAllExceptions(): void
    {
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('First error');
            }))
            ->add(Stage::handler(function ($ctx) {
                throw new RuntimeException('Second error');
            }));

        try {
            $chain->execute();
            $this->fail('Expected AllStagesFailedException was not thrown');
        } catch (AllStagesFailedException $e) {
            $exceptions = $e->getExceptions();
            $this->assertCount(2, $exceptions);
            $this->assertSame('First error', $exceptions[0]->getMessage());
            $this->assertSame('Second error', $exceptions[1]->getMessage());
        }
    }

    public function testOnFailureCallbackIsInvokedOnStageFailure(): void
    {
        $callbackInvoked = false;
        $receivedException = null;
        $receivedContext = null;

        $context = ['test' => 'value'];
        $chain = new FallbackChain($context);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('Stage failed');
            })->onFailure(function ($e, $ctx) use (&$callbackInvoked, &$receivedException, &$receivedContext) {
                $callbackInvoked = true;
                $receivedException = $e;
                $receivedContext = $ctx;
            }))
            ->add(Stage::handler(function ($ctx) {
                return 'fallback succeeded';
            }));

        $chain->execute();

        $this->assertTrue($callbackInvoked);
        $this->assertInstanceOf(Exception::class, $receivedException);
        $this->assertSame('Stage failed', $receivedException->getMessage());
        $this->assertSame($context, $receivedContext);
    }

    public function testOnFailureCallbackIsNotInvokedOnSuccess(): void
    {
        $callbackInvoked = false;
        $chain = new FallbackChain([]);

        $chain->add(Stage::handler(function ($ctx) {
            return 'success';
        })->onFailure(function ($e, $ctx) use (&$callbackInvoked) {
            $callbackInvoked = true;
        }));

        $chain->execute();

        $this->assertFalse($callbackInvoked);
    }

    public function testMultipleOnFailureCallbacksAreInvoked(): void
    {
        $callbacks = [];
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('First');
            })->onFailure(function ($e, $ctx) use (&$callbacks) {
                $callbacks[] = 'first';
            }))
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('Second');
            })->onFailure(function ($e, $ctx) use (&$callbacks) {
                $callbacks[] = 'second';
            }))
            ->add(Stage::handler(function ($ctx) {
                return 'success';
            }));

        $chain->execute();

        $this->assertSame(['first', 'second'], $callbacks);
    }

    public function testExecuteThrowsExceptionWhenNoStagesAdded(): void
    {
        $chain = new FallbackChain([]);

        $this->expectException(AllStagesFailedException::class);

        $chain->execute();
    }

    public function testStageWithoutOnFailureCallbackStillContinues(): void
    {
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('No callback');
            }))
            ->add(Stage::handler(function ($ctx) {
                return 'recovered';
            }));

        $result = $chain->execute();

        $this->assertSame('recovered', $result);
    }

    public function testHandlerCanReturnNull(): void
    {
        $chain = new FallbackChain([]);

        $chain->add(Stage::handler(function ($ctx) {
            return null;
        }));

        $result = $chain->execute();

        $this->assertNull($result);
    }

    public function testHandlerCanReturnFalse(): void
    {
        $chain = new FallbackChain([]);

        $chain->add(Stage::handler(function ($ctx) {
            return false;
        }));

        $result = $chain->execute();

        $this->assertFalse($result);
    }

    public function testHandlerCanReturnZero(): void
    {
        $chain = new FallbackChain([]);

        $chain->add(Stage::handler(function ($ctx) {
            return 0;
        }));

        $result = $chain->execute();

        $this->assertSame(0, $result);
    }

    public function testExceptionInOnFailureDoesNotStopChain(): void
    {
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('First stage failed');
            })->onFailure(function ($e, $ctx) {
                throw new RuntimeException('onFailure callback failed');
            }))
            ->add(Stage::handler(function ($ctx) {
                return 'second stage succeeded';
            }));

        $result = $chain->execute();

        $this->assertSame('second stage succeeded', $result);
    }

    public function testExceptionInOnFailureIsIncludedInAllStagesFailedException(): void
    {
        $chain = new FallbackChain([]);

        $chain
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('Handler failed');
            })->onFailure(function ($e, $ctx) {
                throw new RuntimeException('onFailure failed');
            }))
            ->add(Stage::handler(function ($ctx) {
                throw new Exception('Second handler failed');
            }));

        try {
            $chain->execute();
            $this->fail('Expected AllStagesFailedException was not thrown');
        } catch (AllStagesFailedException $e) {
            $exceptions = $e->getExceptions();
            $this->assertCount(3, $exceptions);
            $this->assertSame('Handler failed', $exceptions[0]->getMessage());
            $this->assertSame('onFailure failed', $exceptions[1]->getMessage());
            $this->assertSame('Second handler failed', $exceptions[2]->getMessage());
        }
    }
}
