#!/bin/bash

# GitHub repository
REPO="TooCodeSavvy/dungeon-crawler"

# Create labels first
echo "Creating labels..."

gh extension install heaths/gh-label

gh label create "epic" --description "Epic - Major feature group" --color "7057ff"
gh label create "core" --description "Core game functionality" --color "0075ca"
gh label create "combat" --description "Combat system related" --color "d73a4a"
gh label create "dungeon" --description "Dungeon generation and navigation" --color "008672"
gh label create "persistence" --description "Save/Load functionality" --color "e4e669"
gh label create "ui/ux" --description "User interface and experience" --color "a2eeef"
gh label create "refactor" --description "Code refactoring" --color "cfd3d7"
gh label create "testing" --description "Test coverage and quality" --color "fbca04"

# Priority Labels
gh label create "priority:high" --description "High priority task" --color "b60205"
gh label create "priority:medium" --description "Medium priority task" --color "d93f0b"
gh label create "priority:low" --description "Low priority task" --color "0e8a16"

# Contribution Labels
gh label create "good first issue" --description "Good for newcomers" --color "7057ff"
gh label create "help wanted" --description "Needs external help" --color "008672"


# Create Milestones
echo "Creating milestones..."

gh api repos/$REPO/milestones -f title="v1.0.0 - MVP" -f description="Minimum Viable Product with core gameplay" -f due_on="2025-09-15T23:59:59Z"
gh api repos/$REPO/milestones -f title="v1.1.0 - Enhanced" -f description="Optional enhancements if time permits" -f due_on="2025-09-15T23:59:59Z"

# Create Epic Issues and Tasks
echo "Creating issues..."

# Epic: Core Game Foundation
EPIC_CORE=$(gh issue create \
  --title "Epic: Core Game Foundation" \
  --body "## Objective
Establish the foundational architecture and core game loop.

## Acceptance Criteria
- [ ] Project structure follows DDD principles
- [ ] Game loop handles player input
- [ ] State management system works
- [ ] Basic CLI interface is functional

## Tasks
- Setup project structure and autoloading
- Implement domain entities
- Create game state manager
- Basic CLI interface" \
  --label "epic,core" \
  --milestone "v1.0.0 - MVP" \
  --assignee "@me" \
  | grep -o '[0-9]*')

# Core Foundation Tasks
gh issue create \
  --title "Setup project structure and autoloading" \
  --body "## Task
- [x] Create folder structure following DDD
- [x] Setup Composer autoloading
- [x] Configure namespaces
- [x] Setup testing infrastructure
- [x] Configure CI/CD pipeline

## Definition of Done
- Composer autoload works
- Tests can be run
- CI pipeline passes" \
  --label "core" \
  --milestone "v1.0.0 - MVP"

gh issue create \
  --title "Implement domain entities" \
  --body "## Task
- [x] Create Player entity
- [x] Create Health value object
- [ ] Create Position value object
- [ ] Create Monster entity
- [ ] Create Room entity
- [ ] Create Treasure entity

## Definition of Done
- All entities have tests
- Value objects are immutable
- Business rules are enforced" \
  --label "core" \
  --milestone "v1.0.0 - MVP"

# Epic: Dungeon System
EPIC_DUNGEON=$(gh issue create \
  --title "Epic: Dungeon System" \
  --body "## Objective
Implement the dungeon with connected rooms and navigation.

## Acceptance Criteria
- [ ] Dungeon has interconnected rooms
- [ ] Player can navigate between rooms
- [ ] Rooms contain monsters/treasure/exits
- [ ] Movement validation works

## Tasks
- Room entity with connections
- Dungeon generator service
- Movement validation
- Room content generation" \
  --label "epic,dungeon" \
  --milestone "v1.0.0 - MVP" \
  | grep -o '[0-9]*')