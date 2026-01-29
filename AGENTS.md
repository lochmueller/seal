# AGENTS.md - Coding Agent Instructions for EXT:seal

This document provides guidance for AI coding agents working on the EXT:seal TYPO3 extension.

## Project Overview

- **Name**: EXT:seal (lochmueller/seal)
- **Type**: TYPO3 CMS Extension
- **Purpose**: Flexible integration of the Search Engine Abstraction Layer (SEAL) into TYPO3
- **Language**: PHP 8.3+
- **Framework**: TYPO3 CMS v13.4 / v14.0
- **Namespace**: `Lochmueller\Seal\`

## Build/Lint/Test Commands

### Code Quality

```bash
# Install dependencies
composer install

# Fix code style issues (php-cs-fixer)
composer code-fix

# Run static analysis (PHPStan level 8)
composer code-check

# Run all unit tests
composer code-test

# Run tests with coverage report
composer code-test-coverage
```

### Running Tests

```bash
# Run all unit tests
.Build/bin/phpunit -c Tests/UnitTests.xml

# Run a specific test file
.Build/bin/phpunit -c Tests/UnitTests.xml Tests/Unit/Path/To/YourTest.php

# Run a specific test method
.Build/bin/phpunit -c Tests/UnitTests.xml --filter testMethodName

# Run tests matching a pattern
.Build/bin/phpunit -c Tests/UnitTests.xml --filter "testSearch"
```

### Direct Tool Commands

```bash
# PHP CS Fixer
.Build/bin/php-cs-fixer fix --allow-unsupported-php-version yes

# PHPStan
.Build/bin/phpstan analyse Classes --memory-limit 1G --level 8

# PHPUnit
.Build/bin/phpunit -c Tests/UnitTests.xml
```

## Project Structure

```
Classes/                    # PHP source code (PSR-4: Lochmueller\Seal\)
  Adapter/Typo3/            # TYPO3 database adapter for SEAL
  Command/                  # Symfony console commands
  Configuration/            # Configuration loading
  Controller/               # Extbase controllers
  Engine/                   # Engine factory
  Event/                    # PSR-14 events
  EventListener/            # Event listeners
  Exception/                # Custom exceptions
  Filter/                   # Search filter implementations
  Handler/                  # Request handlers
  Middleware/               # PSR-15 middleware
  Pagination/               # Pagination helpers
  Schema/                   # Schema management
Configuration/              # TYPO3 configuration (TCA, Services, etc.)
Resources/                  # Templates, translations, icons
Tests/Unit/                 # Unit tests
```

## Code Style Guidelines

### PHP CS Fixer Rules (`.php-cs-fixer.dist.php`)

- **Standard**: PER-CS3.0 with risky rules enabled
- **PHP Version**: 8.3 migration rules applied
- **PHPUnit**: 10.0 migration rules applied
- **Strict Types**: Required in all files
- **Imports**: No unused imports allowed

### Required File Header

Every PHP file MUST start with:

```php
<?php

declare(strict_types=1);

namespace Lochmueller\Seal\YourNamespace;
```

### Import Conventions

1. Group imports by type: PHP core, external packages, TYPO3, then project classes
2. One class per `use` statement
3. No unused imports (enforced by php-cs-fixer)

```php
use DateTimeImmutable;
use CmsIg\Seal\EngineInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use Lochmueller\Seal\Schema\SchemaBuilder;
```

### Type Declarations

- All method parameters MUST have type hints
- All methods MUST have return type declarations
- Use union types when appropriate: `IndexFileEvent|IndexPageEvent`
- Use `?Type` for nullable parameters
- Use PHPDoc for complex generics: `@return iterable<array>`

### Naming Conventions

- **Classes**: PascalCase (e.g., `SearchController`, `EngineFactory`)
- **Methods**: camelCase (e.g., `buildEngineBySite`, `getFilterRows`)
- **Variables**: camelCase (e.g., `$currentPage`, `$searchResult`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `DEFAULT_INDEX`, `TEST_ITERATIONS`)
- **Interfaces**: Suffix with `Interface` (e.g., `FilterInterface`)
- **Exceptions**: Suffix with `Exception` (e.g., `AdapterNotFoundException`)

### Constructor Property Promotion

Use PHP 8.0+ constructor property promotion:

```php
public function __construct(
    private readonly Seal $seal,
    protected ConfigurationLoader $configurationLoader,
    protected Filter $filter,
) {}
```

### Readonly Properties

Use `readonly` modifier for immutable dependencies:

```php
private readonly ResourceFactory $resourceFactory,
private readonly RecordSelection $recordSelection,
```

### Exception Handling

- Create specific exception classes in `Classes/Exception/`
- Exceptions extend base `\Exception`
- Use try-catch with logging for recoverable errors:

```php
try {
    // operation
} catch (\Exception $exception) {
    $this->logger?->error($exception->getMessage(), ['exception' => $exception]);
}
```

### Dependency Injection

- Services defined in `Configuration/Services.yaml` and `Configuration/Services.php`
- Use autowiring (enabled by default)
- Use attributes for configuration:

```php
#[AsEventListener('seal-index')]
public function __invoke(IndexFileEvent|IndexPageEvent $event): void
```

### Controllers

- Extend `AbstractSealController` or TYPO3's `ActionController`
- Action methods return `ResponseInterface`
- Use `$this->htmlResponse()` for HTML responses

## Testing Guidelines

### Test Structure

- Tests go in `Tests/Unit/`
- Extend `Lochmueller\Seal\Tests\Unit\AbstractTest`
- Test class names end with `Test`

```php
<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Tests\Unit\YourNamespace;

use Lochmueller\Seal\Tests\Unit\AbstractTest;

class YourClassTest extends AbstractTest
{
    public function testYourFeature(): void
    {
        // Arrange, Act, Assert
    }
}
```

## Key Dependencies

- `cmsig/seal`: Search Engine Abstraction Layer core library
- `lochmueller/index`: Indexing extension for TYPO3
- `typo3/cms-core`, `typo3/cms-extbase`: TYPO3 framework

## Common Patterns

### Building Search Engine

```php
$engine = $this->seal->buildEngineBySite($site);
$result = $engine->createSearchBuilder(SchemaBuilder::DEFAULT_INDEX)
    ->addFilter(Condition::and(...$filters))
    ->limit($itemsPerPage)
    ->offset($offset)
    ->getResult();
```

### Event Listeners

```php
#[AsEventListener('listener-name')]
public function __invoke(EventType $event): void
{
    // Handle event
}
```

## Before Committing

1. Run `composer code-fix` to fix code style
2. Run `composer code-check` to verify PHPStan passes
3. Run `composer code-test` to ensure tests pass
