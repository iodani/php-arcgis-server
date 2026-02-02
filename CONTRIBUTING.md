# Contributing to PHP ArcGIS Server

Thank you for your interest in contributing to PHP ArcGIS Server! This document provides guidelines and instructions for contributing to this project.

## 🎯 Ways to Contribute

- 🐛 **Report bugs** - Submit detailed bug reports
- ✨ **Suggest features** - Propose new features or improvements
- 📝 **Improve documentation** - Help make docs clearer and more comprehensive
- 💻 **Submit code** - Fix bugs or implement new features
- 🧪 **Add tests** - Improve test coverage
- 🌍 **Add data source adapters** - Support for new frameworks or databases

## 🚀 Getting Started

### 1. Fork and Clone

```bash
# Fork the repository on GitHub
# Then clone your fork
git clone https://github.com/YOUR_USERNAME/php-arcgis-server.git
cd php-arcgis-server
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-bug-fix
```

## 📋 Development Guidelines

### Code Style

This project follows **PSR-12** coding standards.

```bash
# Check code style
composer cs

# Fix code style automatically
composer cs:fix
```

### Static Analysis

All code must pass **PHPStan level 8** analysis.

```bash
# Run static analysis
composer stan
```

### Testing

All new features must include tests.

```bash
# Run tests
composer test

# Run tests with coverage
composer test:coverage
```

### Run All Quality Checks

```bash
# Run all quality checks at once
composer quality
```

## 🏗️ Project Structure

```
src/
├── Constants/          # Constant definitions (FieldType, GeometryType)
├── Contracts/          # Interfaces
├── Core/              # Core classes (FeatureLayer, FeatureServer)
├── DataSource/        # Data source adapters
├── Response/          # Response builders
└── Examples/          # Example implementations

tests/
└── Unit/              # Unit tests

examples/
├── Standalone/        # Standalone PHP examples
└── Yii/              # Yii Framework examples

docs/
├── framework-guides/  # Integration guides for frameworks
└── api/              # API reference documentation
```

## 💻 Submitting Changes

### 1. Write Good Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: Add support for MySQL data source
fix: Correct extent calculation for polygons
docs: Update Yii integration guide
test: Add tests for PostGISDataSource
refactor: Simplify field mapping logic
```

### 2. Push Your Changes

```bash
git add .
git commit -m "feat: your feature description"
git push origin feature/your-feature-name
```

### 3. Open a Pull Request

- Go to the original repository on GitHub
- Click "New Pull Request"
- Select your branch
- Fill in the PR template with:
    - **Description** of changes
    - **Motivation** for the changes
    - **Testing** done
    - Related **issues** (if any)

## 🧪 Testing Guidelines

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\Tests\Unit\DataSource;

use PHPUnit\Framework\TestCase;

class YourTest extends TestCase
{
    public function testYourFeature(): void
    {
        // Arrange
        $dataSource = new YourDataSource();
        
        // Act
        $result = $dataSource->doSomething();
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Test Coverage

- Aim for **80%+ code coverage** for new features
- Test both **happy paths** and **error cases**
- Test **edge cases** and **boundary conditions**

## 📝 Documentation Guidelines

### Code Documentation

Use PHPDoc blocks for all public methods:

```php
/**
 * Query features from a table
 * 
 * @param string $table Table name
 * @param array $params Query parameters (where, outFields, geometry, etc.)
 * @return array Array of feature records
 */
public function query(string $table, array $params = []): array
{
    // Implementation
}
```

### User Documentation

- Update relevant docs in `docs/` directory
- Add examples in `examples/` if applicable
- Update README.md if adding major features

## 🎨 Creating New Data Source Adapters

To add support for a new framework or database:

1. Create a new class implementing `DataSourceInterface`
2. Add to `src/DataSource/`
3. Follow this template:

```php
<?php

declare(strict_types=1);

namespace Iodani\ArcGIS\Server\DataSource;

use Iodani\ArcGIS\Server\Contracts\DataSourceInterface;

class YourDataSource implements DataSourceInterface
{
    public function query(string $table, array $params = []): array
    {
        // Implementation
    }

    public function count(string $table, array $params = []): int
    {
        // Implementation
    }

    public function isAvailable(): bool
    {
        // Implementation
    }

    public function getDb()
    {
        // Implementation
    }
}
```

4. Add tests in `tests/Unit/DataSource/`
5. Add usage example in `examples/`
6. Document in `docs/framework-guides/`

## 🐛 Reporting Bugs

### Before Submitting

1. Check if the bug is already reported in [Issues](https://github.com/iodani/php-arcgis-server/issues)
2. Make sure you're using the latest version
3. Try to reproduce with a minimal example

### Bug Report Template

```markdown
**Describe the bug**
A clear description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Create layer with '...'
2. Query with parameters '...'
3. See error

**Expected behavior**
What you expected to happen.

**Actual behavior**
What actually happened.

**Environment:**
- PHP version: [e.g., 8.1.0]
- Package version: [e.g., 1.0.0]
- Database: [e.g., PostgreSQL 14 with PostGIS 3.2]
- Framework: [e.g., Yii 1.1.20, standalone, etc.]

**Additional context**
Any other relevant information.
```

## ✨ Suggesting Features

### Feature Request Template

```markdown
**Is your feature request related to a problem?**
A clear description of the problem. Ex. I'm always frustrated when [...]

**Describe the solution you'd like**
What you want to happen.

**Describe alternatives you've considered**
Other solutions you've thought about.

**Additional context**
Any other context, mockups, or examples.
```

## 🔍 Code Review Process

1. **Automated checks** must pass (tests, code style, static analysis)
2. **Maintainer review** - A project maintainer will review your code
3. **Feedback** - You may need to make changes based on feedback
4. **Approval** - Once approved, your PR will be merged

## 📜 License

By contributing, you agree that your contributions will be licensed under the MIT License.

## 🤝 Code of Conduct

### Our Standards

- **Be respectful** and inclusive
- **Be collaborative** and constructive
- **Focus on what is best** for the community
- **Show empathy** towards other community members

### Unacceptable Behavior

- Harassment or discriminatory language
- Trolling or insulting comments
- Personal or political attacks
- Publishing others' private information

## 📞 Questions?

- Open a [Discussion](https://github.com/iodani/php-arcgis-server/discussions)
- Open an [Issue](https://github.com/iodani/php-arcgis-server/issues)

## 🙏 Thank You!

Your contributions make this project better for everyone. Thank you for taking the time to contribute!

---

**Happy coding!** 🚀