<?php

declare(strict_types=1);

namespace MahdiMh\FallbackChain;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when all stages in a fallback chain have failed.
 *
 * This exception stores all the exceptions that occurred during the chain
 * execution, allowing for detailed error inspection and logging.
 */
class AllStagesFailedException extends RuntimeException
{
    /**
     * @var Throwable[]
     */
    private $exceptions;

    /**
     * @param string $message The exception message
     * @param Throwable[] $exceptions Array of exceptions from failed stages
     * @param Throwable|null $previous The previous exception for chaining
     */
    public function __construct(
        $message = 'All stages in the fallback chain have failed.',
        array $exceptions = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->exceptions = $exceptions;
    }

    /**
     * Get all exceptions that occurred during chain execution.
     *
     * @return Throwable[]
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }
}
