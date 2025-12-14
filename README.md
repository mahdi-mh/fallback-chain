# Fallback Chain

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mahdi-mh/fallback-chain.svg?style=flat-square)](https://packagist.org/packages/mahdi-mh/fallback-chain)
[![Tests](https://github.com/mahdi-mh/fallback-chain/actions/workflows/tests.yml/badge.svg)](https://github.com/mahdi-mh/fallback-chain/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/mahdi-mh/fallback-chain.svg?style=flat-square)](https://packagist.org/packages/mahdi-mh/fallback-chain)

A lightweight PHP package for building fallback chains with callable handlers and failure callbacks.

## Features

- **Fluent Interface**: Build chains with a clean, readable syntax
- **Automatic Fallback**: Automatically continues to the next stage when one fails
- **Failure Callbacks**: Execute custom logic when a stage fails (logging, metrics, etc.)
- **Context Sharing**: Share data between all stages via a context object
- **Exception Collection**: Access all exceptions when the entire chain fails
- **PHP 7.1+ Compatible**: Works with PHP 7.1 through PHP 8.x

## Installation

Install the package via Composer:

```bash
composer require mahdi-mh/fallback-chain
```

## Usage

### Basic Example

```php
use MahdiMh\FallbackChain\FallbackChain;
use MahdiMh\FallbackChain\Stage;
use MahdiMh\FallbackChain\AllStagesFailedException;

$context = [
    'to'   => '09121234567',
    'text' => 'Hello, this is a test message',
];

$chain = new FallbackChain($context);

$chain
    ->add(Stage::handler(function ($ctx) {
        return SmsProvider1::send($ctx['to'], $ctx['text']);
    })->onFailure(function (\Throwable $e, $ctx) {
        error_log('SMS Provider 1 failed: ' . $e->getMessage());
    }))

    ->add(Stage::handler(function ($ctx) {
        return SmsProvider2::send($ctx['to'], $ctx['text']);
    }))

    ->add(Stage::handler(function ($ctx) {
        return SmsProvider3::send($ctx['to'], $ctx['text']);
    })->onFailure(function (\Throwable $e, $ctx) {
        error_log('Provider 3 unavailable: ' . $e->getMessage());
    }));

try {
    $result = $chain->execute();
    echo "Message sent successfully: " . $result;
} catch (AllStagesFailedException $e) {
    echo "All providers failed.";

    // Access all exceptions that occurred
    foreach ($e->getExceptions() as $exception) {
        error_log($exception->getMessage());
    }
}
```

### How It Works

1. **Create a chain** with a shared context (any value - array, object, etc.)
2. **Add stages** using the fluent `add()` method
3. **Execute the chain** - it tries each stage in order:
   - If a stage succeeds (no exception), its result is returned immediately
   - If a stage fails (throws any `Throwable`), the optional `onFailure` callback runs
   - Execution continues to the next stage
4. **Handle total failure** - if all stages fail, `AllStagesFailedException` is thrown

### Use Cases

- **Multiple SMS/Email Providers**: Try primary provider, fall back to secondary
- **Payment Gateways**: Attempt multiple payment processors
- **API Endpoints**: Try different API servers or mirrors
- **Cache Layers**: Check memory cache, then Redis, then database
- **File Storage**: Try local storage, then S3, then another cloud provider

### Creating Stages

```php
// Stage with just a handler
$stage = Stage::handler(function ($context) {
    return doSomething($context);
});

// Stage with handler and failure callback
$stage = Stage::handler(function ($context) {
    return doSomething($context);
})->onFailure(function (\Throwable $e, $context) {
    // Log, send metrics, cleanup, etc.
});
```

### Accessing Failed Exceptions

When all stages fail, you can inspect all the exceptions:

```php
try {
    $chain->execute();
} catch (AllStagesFailedException $e) {
    $exceptions = $e->getExceptions();

    foreach ($exceptions as $index => $exception) {
        echo "Stage {$index} failed: " . $exception->getMessage() . "\n";
    }
}
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
