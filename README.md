# 🏰 Dungeon Crawler

[![CI Pipeline](https://github.com/TooCodeSavvy/dungeon-crawler/actions/workflows/ci.yml/badge.svg)](https://github.com/TooCodeSavvy/dungeon-crawler/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Code Coverage](https://codecov.io/gh/TooCodeSavvy/dungeon-crawler/branch/main/graph/badge.svg)](https://codecov.io/gh/TooCodeSavvy/dungeon-crawler)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](phpstan.neon)

A text-based dungeon crawler game built with PHP.

## 📖 Table of Contents

- [Features](#-features)
- [Quick Start](#-quick-start)
- [Installation](#-installation)
- [How to Play](#-how-to-play)
- [Development](#-development)
  - [Project Structure](#project-structure)
  - [Git Workflow](#git-workflow)
  - [Testing](#testing)
  - [Code Quality](#code-quality)
- [Contributing](#-contributing)
- [Architecture](#-architecture)
- [CI/CD Pipeline](#-cicd-pipeline)
- [License](#-license)

## ✨ Features

### Core Gameplay
- 🎮 **Turn-based combat** - Strategic battles with monsters
- 🗺️ **Dungeon exploration** - Navigate through interconnected rooms
- ⚔️ **Combat system** - Fight monsters with calculated damage
- 💎 **Treasure collection** - Find valuable items in the dungeon
- 💾 **Save/Load system** - Persist your game progress
- 🏆 **Win conditions** - Find the exit to escape the dungeon

### Technical Features
- 🏗️ **Domain-Driven Design** - Clean architecture with clear separation of concerns
- 🧪 **Full test coverage** - Unit and integration tests
- 🔄 **CI/CD Pipeline** - Automated testing and code quality checks
- 📊 **Code quality tools** - PHPStan, PHP CS Fixer, CodeSniffer
- 📝 **Comprehensive documentation** - Code comments and architectural decisions

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/TooCodeSavvy/dungeon-crawler.git
cd dungeon-crawler

# Install dependencies
composer install

# Run the game
composer game

# Run tests
composer test
```

## 📦 Installation

### Prerequisites

- PHP 8.4 or higher
- Composer 2.8.6
- Git

### Detailed Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/TooCodeSavvy/dungeon-crawler.git
   cd dungeon-crawler
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Verify installation**
   ```bash
   # Run tests to ensure everything works
   composer test
   
   # Check code quality
   composer check
   ```

4. **Start the game**
   ```bash
   composer game
   ```

## 🎮 How to Play

### Starting the Game

```bash
# Start a new game
composer game

# Or directly with PHP
php src/Presentation/game.php
```

### Commands

| Command | Description | Example |
|---------|-------------|---------|
| `north`, `n` | Move north | `> north` |
| `south`, `s` | Move south | `> south` |
| `east`, `e` | Move east | `> east` |
| `west`, `w` | Move west | `> west` |
| `attack`, `a` | Attack monster | `> attack` |
| `look`, `l` | Examine current room | `> look` |
| `inventory`, `i` | Check inventory | `> inventory` |
| `save [name]` | Save game | `> save mysave` |
| `load [name]` | Load game | `> load mysave` |
| `help`, `h` | Show help | `> help` |
| `quit`, `q` | Exit game | `> quit` |

### Game Objectives

1. **Explore** the dungeon by moving between rooms
2. **Fight** monsters you encounter
3. **Collect** treasure for points
4. **Find** the exit to win
5. **Survive** - if your health reaches 0, you lose!

## 🛠️ Development

### Project Structure

```
dungeon-crawler/
├── src/                      # Source code
│   ├── Domain/              # Core business logic
│   │   ├── Entity/         # Game entities (Player, Monster, Room)
│   │   ├── ValueObject/    # Value objects (Health, Position)
│   │   ├── Service/        # Domain services (Combat, Movement)
│   │   └── Repository/     # Repository interfaces
│   ├── Application/         # Application layer
│   │   ├── Command/        # Game commands
│   │   ├── State/          # State management
│   │   └── GameEngine.php # Main game loop
│   ├── Infrastructure/      # External concerns
│   │   ├── Persistence/    # Save/Load implementation
│   │   └── Console/        # CLI handling
│   └── Presentation/        # User interface
│       └── game.php        # Entry point
├── tests/                   # Test suites
│   ├── Unit/               # Unit tests
│   ├── Integration/        # Integration tests
│   └── Feature/            # Feature tests
├── docs/                    # Documentation
├── scripts/                 # Utility scripts
└── storage/                 # Game saves
```

### Testing

#### Running Tests

```bash
# Run all tests
composer test

# Run specific test suites
composer test-unit          # Unit tests only
composer test-integration   # Integration tests only

# Run with coverage
composer test-coverage      # HTML report
composer test-coverage-text # Terminal output

# Run specific test file
composer test -- tests/Unit/Domain/ValueObject/HealthTest.php

# Run tests with filter
composer test -- --filter testHealthCannotGoNegative
```

### Code Quality

#### Available Commands

```bash
# Check code style (PSR-12)
composer cs

# Fix code style automatically
composer cs-fix

# Run static analysis (PHPStan level 8)
composer phpstan

# Run PHP CS Fixer
composer cs-fixer

# Check everything
composer check

# Fix everything possible
composer fix
```

#### Quality Standards

- ✅ **PSR-12** coding standard
- ✅ **PHPStan** level 8 (maximum strictness)
- ✅ **100%** critical path test coverage
- ✅ **Type declarations** for all parameters and returns
- ✅ **PHPDoc** for complex methods
- ✅ **No mixed types** or dynamic properties

## 🏗️ Architecture

### Domain-Driven Design

The project follows DDD principles with clear boundaries:

```
┌─────────────────────────────────────────┐
│          Presentation Layer             │
│         (CLI Interface)                 │
├─────────────────────────────────────────┤
│          Application Layer              │
│     (Commands, State, Game Loop)       │
├─────────────────────────────────────────┤
│           Domain Layer                  │
│  (Entities, Value Objects, Services)   │
├─────────────────────────────────────────┤
│        Infrastructure Layer             │
│    (Persistence, External Services)    │
└─────────────────────────────────────────┘
```

### Design Patterns Used

- **Command Pattern** - Player actions
- **State Pattern** - Game state management
- **Repository Pattern** - Data persistence
- **Factory Pattern** - Entity creation
- **Strategy Pattern** - Combat calculations
- **Value Object Pattern** - Immutable domain concepts

### Key Architectural Decisions

1. **Immutable Value Objects** - Prevent state corruption
2. **Dependency Injection** - Testability and flexibility
3. **Interface Segregation** - Small, focused interfaces
4. **Domain Events** - Decoupled communication (future)
5. **CQRS** - Separate read/write operations (future)

## 🔄 CI/CD Pipeline

Our GitHub Actions pipeline ensures code quality:

### Pipeline Stages

```yaml
on: [push, pull_request]

jobs:
  syntax-check:    # Fast PHP syntax validation
  tests:           # PHPUnit tests on multiple PHP versions
  code-quality:    # PSR-12, PHPStan, CS Fixer
  security-check:  # Dependency vulnerability scan
  integration:     # Game startup and integration tests
```

### Local CI Simulation

```bash
# Run the same checks as CI locally
composer ci

# Or manually:
composer validate --strict
composer check
composer audit
```

### Deployment

```bash
# Create release
git flow release start 1.0.0

# Update version
nano composer.json  # Update version

# Finish release
git flow release finish 1.0.0

# Push to production
git push origin main --tags
```

## 📊 Project Status

### Current Version: 0.1.0-dev

### Roadmap

#### v1.0.0 - MVP (Due: Dec 15, 2024)
- [x] Project setup and architecture
- [x] Health value object
- [ ] Player entity
- [ ] Room and dungeon system
- [ ] Basic combat
- [ ] Save/Load functionality
- [ ] CLI interface

#### v1.1.0 - Enhancements
- [ ] Random dungeon generation
- [ ] Multiple monster types
- [ ] ASCII map display
- [ ] Colored output
- [ ] Item system

#### v2.0.0 - Advanced Features
- [ ] Character classes
- [ ] Skill system
- [ ] Procedural generation
- [ ] Difficulty levels
- [ ] Achievements

## 📈 Metrics

```bash
# Generate metrics
composer metrics

# View test coverage
composer test-coverage
open coverage/index.html
```

## 🐛 Troubleshooting

### Common Issues

**Issue: Game won't start**
```bash
# Check PHP version
php -v  # Should be 8.2+

# Reinstall dependencies
rm -rf vendor composer.lock
composer install
```

**Issue: Tests failing**
```bash
# Clear test cache
rm -rf .phpunit.cache

# Run specific test with verbose output
composer test -- --verbose tests/Unit/YourTest.php
```

**Issue: Permission denied on game save**
```bash
# Fix storage permissions
chmod -R 755 storage/
```

## 📄 License

This project is licensed under the GPL-3.0 License
 
 
---

<div align="center">
  <b>Happy Dungeon Crawling! 🗡️</b>
  <br>
  <sub>Built with ❤️ using PHP</sub>
</div>
```
