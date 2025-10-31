---
name: run-tests
description: "Run Pest tests for TanaVitrine project. Use when running test suite, checking code quality, or before committing changes."
---

# Run Tests

## Instructions

1. **Run all tests** in parallel:

   ```bash
   ./vendor/bin/sail composer test
   ```

2. **Run specific test file**:

   ```bash
   ./vendor/bin/sail artisan test tests/Feature/StoreTest.php
   ```

3. **Run with coverage** (if configured):

   ```bash
   ./vendor/bin/sail artisan test --coverage
   ```

4. **Run code analysis** (PHPStan):

   ```bash
   ./vendor/bin/sail composer analyse
   ```

5. **Run code formatter** (Pint + Rector):

   ```bash
   ./vendor/bin/sail composer format
   ```

## Test Structure

- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`
- Uses Pest testing framework

## Examples

### Run before commit

```bash
./vendor/bin/sail composer format && \
./vendor/bin/sail composer analyse && \
./vendor/bin/sail composer test
```
