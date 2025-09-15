#!/bin/bash

echo "🎮 Dungeon Crawler Development Workflow"
echo "======================================="
echo ""
echo "What would you like to do?"
echo ""
echo "1) Start new feature"
echo "2) Create bugfix"
echo "3) Run tests"
echo "4) Check code quality"
echo "5) Fix code style"
echo "6) Create pull request"
echo "7) View project status"
echo "8) Generate documentation"
echo "9) Build for production"
echo "0) Exit"
echo ""
read -p "Select option: " option

case $option in
    1)
        read -p "Feature name (e.g., combat-system): " feature_name
        git checkout develop
        git pull origin develop
        git checkout -b "feature/$feature_name"
        echo "✅ Created branch: feature/$feature_name"
        echo "Start coding! Remember to make atomic commits."
        ;;
    2)
        read -p "Bug description (e.g., fix-health-calculation): " bug_name
        git checkout develop
        git pull origin develop
        git checkout -b "bugfix/$bug_name"
        echo "✅ Created branch: bugfix/$bug_name"
        ;;
    3)
        echo "Running tests..."
        composer test
        ;;
    4)
        echo "Checking code quality..."
        composer check
        ;;
    5)
        echo "Fixing code style..."
        composer fix
        git add -A
        git commit -m "style: apply code style fixes"
        ;;
    6)
        CURRENT_BRANCH=$(git branch --show-current)
        read -p "PR title: " pr_title
        read -p "PR description: " pr_description
        gh pr create --base develop --head "$CURRENT_BRANCH" --title "$pr_title" --body "$pr_description"
        ;;
    7)
        ./scripts/project-status.sh
        ;;
    8)
        echo "Generating documentation..."
        vendor/bin/phpdoc run -d src -t docs/api
        echo "Documentation generated in docs/api/"
        ;;
    9)
        echo "Building for production..."
        composer install --no-dev --optimize-autoloader
        echo "✅ Production build complete"
        ;;
    0)
        echo "Goodbye! 👋"
        exit 0
        ;;
    *)
        echo "Invalid option"
        ;;
esac