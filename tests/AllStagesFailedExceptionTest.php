<?php

declare(strict_types=1);

namespace MahdiMh\FallbackChain\Tests;

use Exception;
use MahdiMh\FallbackChain\AllStagesFailedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AllStagesFailedExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new AllStagesFailedException();

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testHasDefaultMessage(): void
    {
        $exception = new AllStagesFailedException();

        $this->assertSame('All stages in the fallback chain have failed.', $exception->getMessage());
    }

    public function testCanSetCustomMessage(): void
    {
        $exception = new AllStagesFailedException('Custom error message');

        $this->assertSame('Custom error message', $exception->getMessage());
    }

    public function testCanStoreExceptions(): void
    {
        $exceptions = [
            new Exception('First error'),
            new Exception('Second error'),
        ];

        $exception = new AllStagesFailedException('All failed', $exceptions);

        $this->assertSame($exceptions, $exception->getExceptions());
    }

    public function testExceptionsAreEmptyByDefault(): void
    {
        $exception = new AllStagesFailedException();

        $this->assertSame([], $exception->getExceptions());
    }

    public function testCanSetPreviousException(): void
    {
        $previous = new Exception('Previous error');
        $exception = new AllStagesFailedException('All failed', [], $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
