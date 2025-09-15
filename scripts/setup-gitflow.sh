#!/bin/bash

echo "Setting up Git Flow for Dungeon Crawler Project"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if git flow is installed
if ! command -v git-flow &> /dev/null; then
    echo -e "${YELLOW}Git Flow is not installed. Installing...${NC}"

    # Detect OS and install git-flow
    if [[ "$OSTYPE" == "darwin"* ]]; then
        brew install git-flow
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        sudo apt-get install git-flow
    else
        echo -e "${RED}Please install git-flow manually${NC}"
        exit 1
    fi
fi

# Initialize git flow
echo -e "${GREEN}Initializing Git Flow...${NC}"
git flow init -d

# Create develop branch if it doesn't exist
if ! git show-ref --verify --quiet refs/heads/develop; then
    git checkout -b develop
    git push -u origin develop
fi

# Set develop as default branch for the repo
git symbolic-ref refs/remotes/origin/HEAD refs/remotes/origin/develop

echo -e "${GREEN}✅ Git Flow setup complete!${NC}"
echo ""
echo "Branch structure:"
echo "  main     -> Production releases"
echo "  develop  -> Integration branch"
echo "  feature/ -> New features"
echo "  release/ -> Release preparation"
echo "  hotfix/  -> Emergency fixes"
echo ""
echo "Start a new feature with: git flow feature start <feature-name>"