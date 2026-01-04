<?php

declare(strict_types=1);

namespace MahdiMh\FallbackChain;

use Throwable;

/**
 * A lightweight mechanism for building and executing a chain of callable handlers
 * with automatic fallback behavior on failure.
 *
 * The chain executes stages in order. A stage is considered successful if its
 * handler executes without throwing any Throwable - the chain immediately returns
 * the handler's result and stops.
 *
 * On failure (any Throwable caught), the optional onFailure callback is invoked,
 * and execution continues to the next stage. If all stages fail, an
 * AllStagesFailedException is thrown.
 */
class FallbackChain
{
    /**
     * @var mixed
     */
    private $context;

    /**
     * @var Stage[]
     */
    private $stages = [];

    /**
     * Create a new fallback chain with the given context.
     *
     * @param mixed|null $context The shared context passed to all handlers and callbacks
     */
    public function __construct($context = null)
    {
        $this->context = $context;
    }

    /**
     * Add a stage to the chain.
     *
     * @param Stage $stage The stage to add
     * @return self
     */
    public function add(Stage $stage)
    {
        $this->stages[] = $stage;

        return $this;
    }

    /**
     * Execute the chain and return the result of the first successful stage.
     *
     * @return mixed The result of the first successful handler
     * @throws AllStagesFailedException When all stages fail
     */
    public function execute()
    {
        $exceptions = [];

        foreach ($this->stages as $stage) {
            try {
                $handler = $stage->getHandler();
                return $handler($this->context);
            } catch (Throwable $e) {
                $exceptions[] = $e;

                $onFailure = $stage->getOnFailure();
                if ($onFailure !== null) {
                    try {
                        $onFailure($e, $this->context);
                    } catch (Throwable $onFailureException) {
                        // Add the onFailure exception to the exceptions list
                        // but continue with the next stage
                        $exceptions[] = $onFailureException;
                    }
                }
            }
        }

        throw new AllStagesFailedException(
            'All stages in the fallback chain have failed.',
            $exceptions
        );
    }
}
