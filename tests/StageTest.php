<?php

declare(strict_types=1);

namespace MahdiMh\FallbackChain\Tests;

use MahdiMh\FallbackChain\Stage;
use PHPUnit\Framework\TestCase;

class StageTest extends TestCase
{
    public function testCanCreateStageWithHandler(): void
    {
        $handler = function ($context) {
            return 'result';
        };

        $stage = Stage::handler($handler);

        $this->assertInstanceOf(Stage::class, $stage);
    }

    public function testCanGetHandlerFromStage(): void
    {
        $handler = function ($context) {
            return 'test result';
        };

        $stage = Stage::handler($handler);

        $this->assertSame($handler, $stage->getHandler());
    }

    public function testOnFailureReturnsFluentInterface(): void
    {
        $handler = function ($context) {
            return 'result';
        };
        $onFailure = function (\Throwable $e, $context) {
            // log error
        };

        $stage = Stage::handler($handler);
        $result = $stage->onFailure($onFailure);

        $this->assertSame($stage, $result);
    }

    public function testCanGetOnFailureCallback(): void
    {
        $handler = function ($context) {
            return 'result';
        };
        $onFailure = function (\Throwable $e, $context) {
            // log error
        };

        $stage = Stage::handler($handler)->onFailure($onFailure);

        $this->assertSame($onFailure, $stage->getOnFailure());
    }

    public function testOnFailureIsNullByDefault(): void
    {
        $handler = function ($context) {
            return 'result';
        };

        $stage = Stage::handler($handler);

        $this->assertNull($stage->getOnFailure());
    }
}
