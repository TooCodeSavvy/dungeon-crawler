# 📜 Scripts Directory

This directory contains automation scripts to streamline development workflow, project setup, and maintenance tasks.

## 🚀 Quick Start

Before using any scripts, make them executable:
```bash
chmod +x scripts/*.sh
```

## 📋 Available Scripts

### 🔧 Core Setup Scripts

#### `initialize-project.sh`
**Purpose**: One-time project initialization script that sets up the entire development environment.

```bash
./scripts/initialize-project.sh
```

**What it does:**
- Creates project directory structure
- Installs Composer dependencies
- Sets up Git hooks
- Initializes Git Flow
- Configures testing framework
- Creates initial GitHub issues

**When to use**: Once, when first setting up the project locally.

---

#### `setup-gitflow.sh`
**Purpose**: Configures Git Flow branching model for the project.

```bash
./scripts/setup-gitflow.sh
```

**What it does:**
- Checks/installs git-flow extension
- Initializes git-flow with default settings
- Creates and pushes develop branch
- Sets develop as default branch

**When to use**: After cloning the repository for the first time.

---

### 📊 GitHub Integration Scripts

#### `create-github-issues.sh`
**Purpose**: Creates individual GitHub issues with labels and milestones.

```bash
# Requires GitHub CLI authentication
gh auth login

# Run the script
./scripts/create-github-issues.sh
```

**What it does:**
- Creates project labels (epic, core, combat, etc.)
- Sets up milestones (v1.0.0, v1.1.0)
- Creates selected high-priority issues
- Assigns issues to you

**When to use**: When you need to create specific issues or reset issue tracking.

---

#### `setup-all-issues.sh`
**Purpose**: Creates the complete set of GitHub issues for the entire project.

```bash
./scripts/setup-all-issues.sh
```

**What it does:**
- Creates ALL project epics
- Creates ALL user stories and tasks
- Links related issues
- Sets up complete project backlog

**When to use**: Once at project start to populate the complete backlog.

---

### 🔄 Development Workflow Scripts

#### `dev-workflow.sh`
**Purpose**: Interactive development assistant for daily workflow tasks.

```bash
./scripts/dev-workflow.sh
```

**Features:**
- Start new feature from issue
- Create pull request
- Run tests and checks
- Update issue status
- Merge completed features

**Example workflow:**
```bash
./scripts/dev-workflow.sh
# Select: 1) Start new feature
# Enter issue number: 5
# Creates: feature/issue-5-implement-room-entity
```

**When to use**: Daily, for any development task.

---

#### `project-status.sh`
**Purpose**: Displays comprehensive project status and metrics.

```bash
./scripts/project-status.sh
```

**What it shows:**
- Current branch and changes
- Open issues assigned to you
- PR status
- Test coverage percentage
- Code quality metrics
- Sprint/milestone progress

**When to use**: During standups or to check project health.

--- 

## 🔐 Prerequisites

### Required Tools
- **Git** (v2.30+)
- **GitHub CLI** (`gh`) - [Install guide](https://cli.github.com/)
- **Composer** - [Install guide](https://getcomposer.org/)
- **PHP** (8.2+)
- **Git Flow** (optional, script will install)

### Setup GitHub CLI
```bash
# Install (macOS)
brew install gh

# Install (Ubuntu/Debian)
sudo apt install gh

# Authenticate
gh auth login
```

## 💡 Best Practices

1. **Always run from project root**
   ```bash
   cd /path/to/dungeon-crawler
   ./scripts/script-name.sh
   ```

2. **Check script requirements**
    - Some scripts need GitHub CLI authentication
    - Some modify git configuration
    - Some require clean working directory

3. **Use dev-workflow.sh for daily tasks**
    - It handles branching conventions
    - Ensures consistent workflow
    - Reduces manual git commands

4. **Run project-status.sh before meetings**
    - Quick project health check
    - Identifies blockers
    - Shows progress metrics

## 🚨 Troubleshooting

### Permission Denied
```bash
chmod +x scripts/*.sh
```

### GitHub CLI Not Authenticated
```bash
gh auth status  # Check status
gh auth login   # Re-authenticate
```

### Script Fails on Windows
Use Git Bash or WSL (Windows Subsystem for Linux) to run bash scripts.

### Git Flow Commands Not Found
```bash
# The setup script should install it, but manually:
brew install git-flow        # macOS
apt-get install git-flow     # Ubuntu/Debian
```