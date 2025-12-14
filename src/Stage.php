<?php

declare(strict_types=1);

namespace MahdiMh\FallbackChain;

/**
 * Represents a single stage in a fallback chain.
 *
 * A stage consists of a primary handler (callable) and an optional
 * failure callback that is invoked when the handler throws an exception.
 */
class Stage
{
    /**
     * @var callable
     */
    private $handler;

    /**
     * @var callable|null
     */
    private $onFailureCallback;

    /**
     * @param callable $handler The primary handler for this stage
     */
    private function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Create a new stage with the given handler.
     *
     * @param callable $handler The primary handler for this stage
     * @return self
     */
    public static function handler(callable $handler)
    {
        return new self($handler);
    }

    /**
     * Set the failure callback for this stage.
     *
     * @param callable $callback The callback to invoke on failure
     * @return self
     */
    public function onFailure(callable $callback)
    {
        $this->onFailureCallback = $callback;

        return $this;
    }

    /**
     * Get the handler for this stage.
     *
     * @return callable
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get the failure callback for this stage.
     *
     * @return callable|null
     */
    public function getOnFailure()
    {
        return $this->onFailureCallback;
    }
}
