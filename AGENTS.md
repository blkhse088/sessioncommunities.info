# AGENTS.md

This file contains build/lint/test commands and code style guidelines for agentic coding agents working in this Session Communities listing project.

## Project Overview

This is a PHP-based web application that crawls Session Communities and displays them as a static HTML website. The project includes:
- PHP backend for fetching and processing community data
- Static HTML generation 
- Feather icons library (Node.js/JavaScript)
- Make-based build system

## Build/Test Commands

### Main Commands (Run from project root)
```bash
# Build everything (fetch data + generate HTML + QR codes + listings)
make all

# Quick build without data fetching
make sco

# Fetch fresh community data
make fetch

# Generate HTML only (using existing data)
make html

# Generate QR codes
make qr-codes

# Clean build artifacts
make clean

# Run tests (non-interactive)
make test-noninteractive

# Full test with browser launch
make test

# Development server with file watching
make dev

# Development server on LAN
make lan-dev
```

### PHP Scripts
```bash
# Individual PHP scripts (run from project root)
/bin/php php/fetch-servers.php [flags]
/bin/php php/generate-html.php [flags]
/bin/php php/generate-qr-codes.php [flags]
/bin/php php/generate-listings.php [flags]
```

### Feather Icons (Node.js)
```bash
cd feather
npm install
npm test                    # Run all tests
npm run test:watch         # Watch mode
npm run test:coverage      # With coverage
npm run lint               # ESLint
npm run format             # Prettier
npm run build              # Build icons
```

## Code Style Guidelines

### PHP Code Style

#### Documentation
- All PHP files must start with `/** \file */` comment block
- Use proper PHPDoc for all functions and global variables
- Include `@var` type declarations for global variables
- Use `@param` and `@return` for function parameters and return types

#### Formatting
- Use tabs for indentation (existing code uses tabs)
- Opening brace `{` on new line for functions and classes
- Space around operators (`$a = $b + $c`)
- No trailing whitespace
- Files should end with a single newline

#### Naming Conventions
- Variables: `snake_case` (`$room_servers`, `$cache_root`)
- Functions: `snake_case` (`count_rooms()`, `truncate()`)
- Classes: `PascalCase` (`CommunityServer`, `LocalConfig`)
- Constants: `UPPER_SNAKE_CASE` (`$REGEX_JOIN_LINK`)
- Files: `kebab-case.php` (`fetch-servers.php`, `generate-html.php`)

#### Import/Require Style
- Use `require_once` for including PHP files
- Group requires at top of files after file header
- Order: core files first, then utils, then specific modules

#### Error Handling
- Check `file_exists()` before file operations
- Use `or mkdir($path, 0700)` for directory creation
- Handle cURL failures gracefully
- Use try-catch for exceptions where appropriate

#### Type Safety
- Use type hints in function signatures: `function foo(array $servers): int`
- Declare variable types in PHPDoc: `/** @var CommunityServer[] $servers */`
- Use strict comparison operators (`===`, `!==`)

### JavaScript/Feather Icons Style

#### Import/Export
- Use ES6 imports/exports
- Default exports for main functionality
- Named exports for utilities

#### Formatting (Prettier config)
- Single quotes: `'single quotes'`
- Trailing commas: `all`
- Arrow function parentheses: `avoid`

#### Testing
- Use Jest framework
- Snapshot testing for UI components
- Mock external dependencies
- Test files: `*.test.js` in `__tests__` directories

### General Project Guidelines

#### File Structure
- PHP files in `php/` directory
- Static output to `output/` directory
- Cache in `cache/` and `cache-lt/`
- Custom content in `custom/`
- Templates in `sites/`

#### Environment Variables
- All paths defined in `.phpenv.php`
- Use global variables for file paths
- Do not hardcode paths in scripts
- UTC timezone: `date_default_timezone_set('UTC')`

#### Build System
- Use Make for orchestration
- Scripts should support `--verbose` and `--dry-run` flags
- Exit with appropriate status codes
- Clean up temporary files

#### Security
- Validate all input data
- Escape HTML output
- Use HTTPS URLs where possible
- Do not expose sensitive information in error messages

#### Performance
- Use caching for network requests
- Batch file operations
- Process data in streams for large datasets
- Clean up temporary resources

## Development Workflow

1. Use `make dev` for development with file watching
2. Run `make test-noninteractive` before committing
3. Use `make fetch` to update community data
4. Check PHP syntax with `php -l file.php`
5. Run `npm run lint` in feather directory for JS changes
6. Test with `make test` for full integration test

## Testing

### PHP Testing
- Use `make test-noninteractive` for CI-style testing
- Test with `make test` for full browser testing
- Verify output in `output/` directory
- Check generated JSON files are valid

### JavaScript Testing
```bash
cd feather
npm test                    # All tests
npm run test:watch         # Development mode
npm run test:coverage      # Coverage report
```

### Single Test Execution
```bash
# PHP - Run individual script with flags
/bin/php php/fetch-servers.php --verbose --dry-run

# JavaScript - Run specific test file
cd feather
npm test -- --testNamePattern="specific test name"
npm test src/__tests__/replace.test.js
```

## Common Issues

- PHP requires `php-curl` extension
- Use `torsocks curl` for Tor network access
- Feather icons require Node.js and npm
- Make sure `cache/` and `output/` directories are writable
- Use `make clean` to reset build artifacts if needed