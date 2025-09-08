#!/bin/bash

echo "🚀 Initializing Dungeon Crawler Project"
echo "======================================="

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Create necessary directories
echo "Creating project directories..."
mkdir -p src/{Domain/{Entity,ValueObject,Service,Repository},Application/{Command,State},Infrastructure/{Persistence,Console},Presentation}
mkdir -p tests/{Unit,Integration,Feature}
mkdir -p storage/saves
mkdir -p var/{cache,logs}
mkdir -p docs/api
mkdir -p build/coverage

# Make scripts executable
echo "Setting up scripts..."
chmod +x scripts/*.sh 2>/dev/null
chmod +x .githooks/* 2>/dev/null

# Configure git hooks
echo "Configuring Git hooks..."
git config core.hooksPath .githooks

# Install dependencies
echo "Installing dependencies..."
composer install

# Fix all PHP files at once:
find . -name "*.php" -type f -exec dos2unix {} \; 2>/dev/null || \
find . -name "*.php" -type f -exec sed -i 's/\r$//' {} \;

# Prevent future issues - create .gitattributes
cat > .gitattributes << 'EOF'
# Ensure all text files use LF line endings
* text=auto eol=lf

# Explicitly declare text files
*.php text eol=lf
*.json text eol=lf
*.md text eol=lf
*.yml text eol=lf
*.yaml text eol=lf
*.xml text eol=lf
*.sh text eol=lf

# Declare files that will always have CRLF line endings on checkout
*.bat text eol=crlf

# Declare binary files
*.png binary
*.jpg binary
*.jpeg binary
*.gif binary
EOF

# Run initial checks
echo -e "${YELLOW}Running initial quality checks...${NC}"
composer check || true

# Setup Git Flow if not already done
if ! git config --get gitflow.branch.master > /dev/null 2>&1; then
    echo "Setting up Git Flow..."
    git flow init -d
fi

# Display status
echo ""
echo -e "${GREEN}✅ Project initialized successfully!${NC}"
echo ""
echo "Next steps:"
echo "1. Run: ./scripts/dev-workflow.sh to start development"
echo "2. View: ./scripts/project-status.sh for project status"
echo "3. Create feature: git flow feature start <feature-name>"
echo ""
echo "Happy coding! 🎮"