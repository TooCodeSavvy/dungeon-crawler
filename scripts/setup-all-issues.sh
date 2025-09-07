#!/bin/bash

# Complete issue creation script
# This creates all issues for the project

echo "📋 Creating all GitHub issues for Dungeon Crawler project..."

# Function to create issue and return its number
create_issue() {
    local title="$1"
    local body="$2"
    local labels="$3"
    local milestone="$4"

    gh issue create \
        --title "$title" \
        --body "$body" \
        --label "$labels" \
        --milestone "$milestone" \
        --assignee "@me"
}

# Epic 1: Core Game Foundation
create_issue \
    "Epic: Core Game Foundation" \
    "$(cat <<EOF
## Objective
Establish the foundational architecture and core game loop.

## User Stories
- As a developer, I want a clean architecture so the code is maintainable
- As a player, I want to start the game and see instructions
- As a player, I want the game to remember its state between commands

## Acceptance Criteria
- [ ] Project follows DDD principles
- [ ] Game loop processes commands correctly
- [ ] State management preserves game state
- [ ] Error handling prevents crashes

## Sub-tasks
See linked issues below
EOF
    )" \
    "epic,core,priority:high" \
    "v1.0.0 - MVP"

# Core tasks
create_issue \
    "Implement Position value object" \
    "$(cat <<EOF
## Description
Create an immutable Position value object for tracking locations in the dungeon.

## Requirements
- [ ] X and Y coordinates
- [ ] Immutable (methods return new instances)
- [ ] Validation (non-negative coordinates)
- [ ] Movement methods (north, south, east, west)
- [ ] Equality comparison
- [ ] Distance calculation

## Tests
- [ ] Valid position creation
- [ ] Movement in all directions
- [ ] Boundary validation
- [ ] Equality checks
EOF
    )" \
    "core,good first issue" \
    "v1.0.0 - MVP"

create_issue \
    "Implement Player entity" \
    "$(cat <<EOF
## Description
Create the Player entity with health, position, and inventory.

## Requirements
- [ ] Health management
- [ ] Position tracking
- [ ] Attack power
- [ ] Inventory (optional for v1.0)
- [ ] Status methods (isAlive, canMove)

## Tests
- [ ] Player creation
- [ ] Taking damage
- [ ] Death state
- [ ] Movement
EOF
    )" \
    "core" \
    "v1.0.0 - MVP"

# Epic 2: Dungeon System
create_issue \
    "Epic: Dungeon System" \
    "$(cat <<EOF
## Objective
Create a navigable dungeon with interconnected rooms.

## User Stories
- As a player, I want to explore different rooms
- As a player, I want to find the exit to win
- As a player, I want to discover treasure and monsters

## Acceptance Criteria
- [ ] Rooms are connected logically
- [ ] Player can move between connected rooms
- [ ] Each room has unique content
- [ ] Exit room exists and is reachable

## Sub-tasks
See linked issues below
EOF
    )" \
    "epic,dungeon,priority:high" \
    "v1.0.0 - MVP"

# Dungeon tasks
create_issue \
    "Implement Room entity" \
    "$(cat <<EOF
## Description
Create Room entity representing a single dungeon location.

## Requirements
- [ ] Unique identifier
- [ ] Description text
- [ ] Connections to other rooms (north, south, east, west)
- [ ] Content (monster, treasure, exit, empty)
- [ ] Visited flag for map display

## Tests
- [ ] Room creation
- [ ] Connection management
- [ ] Content assignment
- [ ] Visited tracking
EOF
    )" \
    "dungeon" \
    "v1.0.0 - MVP"

create_issue \
    "Create Dungeon Generator service" \
    "$(cat <<EOF
## Description
Service to generate random or predefined dungeon layouts.

## Requirements
- [ ] Generate connected room graph
- [ ] Ensure no isolated rooms
- [ ] Place exit room
- [ ] Distribute monsters and treasure
- [ ] Configurable difficulty

## Tests
- [ ] Valid dungeon generation
- [ ] All rooms reachable
- [ ] Exit exists
- [ ] Content distribution
EOF
    )" \
    "dungeon" \
    "v1.0.0 - MVP"

# Epic 3: Combat System
create_issue \
    "Epic: Combat System" \
    "$(cat <<EOF
## Objective
Implement turn-based combat between player and monsters.

## User Stories
- As a player, I want to fight monsters
- As a player, I want to see combat results
- As a player, I want strategic combat choices

## Acceptance Criteria
- [ ] Turn-based combat flow
- [ ] Damage calculation
- [ ] Combat ends when one party dies
- [ ] Clear combat feedback

## Sub-tasks
See linked issues below
EOF
    )" \
    "epic,combat,priority:high" \
    "v1.0.0 - MVP"

# Combat tasks
create_issue \
    "Implement Combat Service" \
    "$(cat <<EOF
## Description
Core combat logic and turn management.

## Requirements
- [ ] Attack resolution
- [ ] Damage calculation
- [ ] Turn order management
- [ ] Combat state tracking
- [ ] Victory/defeat conditions

## Tests
- [ ] Basic attack
- [ ] Player death
- [ ] Monster death
- [ ] Damage calculation
EOF
    )" \
    "combat" \
    "v1.0.0 - MVP"

# Epic 4: Persistence
create_issue \
    "Epic: Persistence System" \
    "$(cat <<EOF
## Objective
Allow players to save and load game progress.

## User Stories
- As a player, I want to save my progress
- As a player, I want to continue from where I left off

## Acceptance Criteria
- [ ] Save current game state
- [ ] Load saved game
- [ ] Multiple save slots (optional)
- [ ] Error handling for corrupted saves

## Sub-tasks
See linked issues below
EOF
    )" \
    "epic,persistence,priority:medium" \
    "v1.0.0 - MVP"

# Add more issues as needed...

echo "✅ All issues created successfully!"
echo ""
echo "View your issues at: https://github.com/TooCodeSavvy/dungeon-crawler/issues"
echo "Set up project board at: https://github.com/TooCodeSavvy/dungeon-crawler/projects"