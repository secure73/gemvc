# Code Quality - PHPStan Integration

## Overview

GEMVC provides optional PHPStan integration during project initialization to help catch bugs and improve code quality through static analysis.

## Features

- **Automatic Installation**: PHPStan is installed as a development dependency via Composer
- **Configuration File**: Pre-configured `phpstan.neon` file optimized for GEMVC projects
- **Composer Scripts**: Convenient commands for running PHPStan analysis
- **Non-Interactive Support**: Automatically skips in non-interactive mode

## Installation

PHPStan is offered during the `gemvc init` command. When you run:

```bash
vendor/bin/gemvc init
```

You'll be prompted to install PHPStan after the core project setup is complete.

### Installation Flow

1. **Project Initialization**: Core GEMVC project structure is created
2. **PHPStan Offer**: CLI prompts for PHPStan installation
3. **Automatic Setup**: If accepted, PHPStan is installed and configured
4. **Ready to Use**: PHPStan is immediately available for code analysis

## Configuration

### PHPStan Configuration File

A pre-configured `phpstan.neon` file is copied to your project root:

```neon
parameters:
    level: 8
    paths:
        - app
    excludePaths:
        - app/vendor
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

### Composer Scripts

The following scripts are automatically added to your `composer.json`:

```json
{
    "scripts": {
        "phpstan": "phpstan analyse",
        "phpstan:check": "phpstan analyse --error-format=json"
    }
}
```

## Usage

### Running PHPStan

```bash
# Basic analysis
composer phpstan

# JSON output for CI/CD
composer phpstan:check

# Direct PHPStan command
./vendor/bin/phpstan analyse

# With custom level
./vendor/bin/phpstan analyse --level=5
```

### Integration with Development Workflow

- **Pre-commit**: Run PHPStan before committing code
- **CI/CD**: Use `phpstan:check` script for automated analysis
- **IDE**: Many IDEs support PHPStan integration for real-time feedback

## Benefits

- **Bug Prevention**: Catches potential issues before runtime
- **Code Quality**: Enforces consistent coding standards
- **Refactoring Safety**: Ensures changes don't introduce new issues
- **Team Collaboration**: Maintains code quality across team members

## Customization

### Adjusting Analysis Level

Modify the `level` parameter in `phpstan.neon`:

```neon
parameters:
    level: 5  # Less strict
    # or
    level: 9  # More strict
```

### Adding Custom Rules

```neon
parameters:
    level: 8
    paths:
        - app
    rules:
        - PHPStan\Rules\Functions\CallToNonExistentFunctionRule
```

### Excluding Paths

```neon
parameters:
    excludePaths:
        - app/vendor
        - app/tests
        - app/cache
```

## Troubleshooting

### Common Issues

1. **Memory Issues**: Increase PHP memory limit for large projects
2. **Slow Analysis**: Use `--memory-limit` flag or exclude unnecessary paths
3. **False Positives**: Adjust level or add specific exclusions

### Performance Tips

- Exclude vendor and cache directories
- Use appropriate analysis level for your project
- Consider running analysis only on changed files in development

## Best Practices

1. **Start with Level 5**: Begin with a moderate strictness level
2. **Gradually Increase**: Incrementally raise the level as code quality improves
3. **Regular Analysis**: Run PHPStan regularly, not just before releases
4. **Team Agreement**: Establish coding standards that align with PHPStan rules

## Integration with Other Tools

- **PHPUnit/Pest**: Run tests after PHPStan analysis
- **Git Hooks**: Pre-commit hooks for automatic checking
- **CI/CD**: Automated quality gates in deployment pipelines
- **IDE Extensions**: Real-time feedback in development environments

---

*PHPStan integration helps maintain high code quality standards in GEMVC projects while providing flexibility for different project requirements.*
