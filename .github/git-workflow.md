# Git Workflow Guide

## Branch Naming Convention
- `feature/` - New features (e.g., `feature/combat-system`)
- `bugfix/` - Bug fixes in develop (e.g., `bugfix/health-calculation`)
- `hotfix/` - Emergency fixes in production (e.g., `hotfix/save-corruption`)
- `release/` - Release preparation (e.g., `release/1.0.0`)
- `chore/` - Maintenance tasks (e.g., `chore/update-dependencies`)

## Workflow Steps

### Starting a New Feature
```bash
# Make sure you're on develop and up-to-date
git checkout develop
git pull origin develop

# Start new feature
git flow feature start <feature-name>
# OR manually
git checkout -b feature/<feature-name>

# Work on feature...
git add .
git commit -m "feat: implement feature X"

# Publish feature for collaboration
git flow feature publish <feature-name>
# OR
git push -u origin feature/<feature-name>

### Creating a Pull Request
# Push your changes
git push origin feature/<feature-name>

# Create PR via GitHub CLI
gh pr create \
  --base develop \
  --head feature/<feature-name> \
  --title "feat: implement feature X" \
  --body "## Description
  Describe your changes
  
  ## Related Issues
  Closes #123
  
  ## Testing
  - [ ] Unit tests pass
  - [ ] Manual testing completed"

### Release Process
# Start release
git flow release start 1.0.0

# Update version numbers, changelog
nano composer.json
nano CHANGELOG.md

# Finish release
git flow release finish 1.0.0

# Push everything
git push origin main
git push origin develop
git push origin --tags