# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2025-12-14

### Added
- Initial release
- `FallbackChain` class for building and executing fallback chains
- `Stage` class for defining individual stages with handlers and failure callbacks
- `AllStagesFailedException` for handling complete chain failure
- Fluent interface for building chains
- Context sharing between all stages
- Collection of all exceptions when chain fails
- Full PHPUnit test suite
- Support for PHP 7.1 through PHP 8.x
