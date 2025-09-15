#!/bin/bash
echo "📊 Dungeon Crawler Project Status"
echo "================================="
echo ""
# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Check for GitHub CLI and authenticate if needed
echo -e "${BLUE}GitHub CLI Status:${NC}"
if ! command -v gh &>/dev/null; then
    echo -e "  GitHub CLI: ${YELLOW}⚠️ Not installed${NC}"
    echo -e "  To install GitHub CLI:"
    echo -e "    - Debian/Ubuntu: ${GREEN}sudo apt install gh${NC}"
    echo -e "    - macOS: ${GREEN}brew install gh${NC}"
    echo -e "    - See more: https://github.com/cli/cli#installation"
    echo ""
    GH_INSTALLED=false
else
    echo -e "  GitHub CLI: ${GREEN}✅ Installed${NC}"
    GH_INSTALLED=true

    # Check authentication status without prompting for re-auth
    if ! gh auth status &>/dev/null; then
        # Check if token exists in config
        TOKEN_PATH="${XDG_CONFIG_HOME:-$HOME/.config}/gh/hosts.yml"
        if [[ -f "$TOKEN_PATH" ]] && grep -q "oauth_token" "$TOKEN_PATH"; then
            echo -e "  Auth Status: ${YELLOW}⚠️ Token exists but session expired${NC}"
            echo -e "  Try refreshing with: ${GREEN}gh auth refresh${NC}"
        else
            echo -e "  Auth Status: ${YELLOW}⚠️ Not authenticated${NC}"
            echo -e "  To authenticate once: ${GREEN}gh auth login${NC}"
            echo -e "  Note: After authenticating, credentials will be saved"
        fi

        # Offer to authenticate now, but only once per day
        AUTH_MARKER="/tmp/gh_auth_prompted_$(date +%Y%m%d)"
        if [[ ! -f "$AUTH_MARKER" ]]; then
            read -p "  Would you like to authenticate now? (y/n) " -n 1 -r
            echo
            touch "$AUTH_MARKER"

            if [[ $REPLY =~ ^[Yy]$ ]]; then
                echo "  Running authentication..."
                if gh auth login; then
                    echo -e "  ${GREEN}Authentication successful!${NC}"
                    echo -e "  ${GREEN}Your credentials are now saved for future sessions${NC}"
                else
                    echo -e "  ${RED}Authentication failed.${NC}"
                fi
                echo ""
            fi
        fi
    else
        echo -e "  Auth Status: ${GREEN}✅ Authenticated${NC}"
    fi
fi
echo ""

# Git information
echo -e "${BLUE}Git Status:${NC}"
CURRENT_BRANCH=$(git branch --show-current)
echo "  Current Branch: $CURRENT_BRANCH"
COMMITS_AHEAD=$(git rev-list --count origin/develop..HEAD 2>/dev/null || echo "0")
echo "  Commits ahead of develop: $COMMITS_AHEAD"
echo ""
# Code statistics
echo -e "${BLUE}Code Statistics:${NC}"
echo -n "  PHP Files: "
find src tests -name "*.php" 2>/dev/null | wc -l
echo -n "  Lines of Code: "
find src -name "*.php" -exec wc -l {} + 2>/dev/null | tail -1 | awk '{print $1}'
echo -n "  Test Files: "
find tests -name "*Test.php" 2>/dev/null | wc -l
echo ""
# Test coverage (if available)
if [ -f "build/coverage/index.html" ]; then
    echo -e "${BLUE}Test Coverage:${NC}"
    grep -oP 'Total.*?<td class=".*?">\K[0-9.]+(?=%)' build/coverage/index.html | head -1 | xargs echo "  Coverage: {}%"
    echo ""
fi
# GitHub Issues (if gh CLI is installed and authenticated)
if $GH_INSTALLED && gh auth status &>/dev/null; then
    echo -e "${BLUE}GitHub Issues:${NC}"
    echo -n "  Open Issues: "
    gh issue list --state open --json number --jq '. | length' 2>/dev/null || echo "N/A"
    echo -n "  In Progress: "
    gh issue list --state open --label "in-progress" --json number --jq '. | length' 2>/dev/null || echo "N/A"
    echo ""
elif $GH_INSTALLED; then
    echo -e "${BLUE}GitHub Issues:${NC}"
    echo "  Not available (authentication required)"
    echo ""
fi
# TODOs in code
echo -e "${BLUE}TODOs in Code:${NC}"
TODO_COUNT=$(grep -r "TODO\|FIXME\|HACK" src tests --include="*.php" 2>/dev/null | wc -l)
echo "  Found: $TODO_COUNT"
if [ $TODO_COUNT -gt 0 ]; then
    echo "  Recent TODOs:"
    grep -r "TODO\|FIXME\|HACK" src tests --include="*.php" 2>/dev/null | head -3 | sed 's/^/    /'
fi
echo ""
# Quality metrics
echo -e "${BLUE}Code Quality:${NC}"
echo -n "  PHPStan: "
if composer phpstan 2>&1 | grep -q "[OK] No errors"; then
    echo -e "${GREEN}✅ Pass${NC}"
else
    echo -e "${YELLOW}⚠️  Has issues${NC}"
fi
echo -n "  CS Fixer: "
if vendor/bin/php-cs-fixer fix --dry-run --diff 2>&1 | grep -q "Found 0 of"; then
    echo -e "${GREEN}✅ Pass${NC}"
else
    echo -e "${YELLOW}⚠️  Needs fixing${NC}"
fi
echo -n "  CodeSniffer: "
if vendor/bin/phpcs --standard=PSR12 src tests 2>&1 | grep -q "0 ERRORS AND 0 WARNINGS"; then
    echo -e "${GREEN}✅ Pass${NC}"
else
    echo -e "${YELLOW}⚠️  Has issues${NC}"
fi
echo ""
# Timeline - FIXED DATES
echo -e "${BLUE}Timeline:${NC}"
START_DATE="2025-09-06"
END_DATE="2025-09-15"
CURRENT_DATE=$(date +%Y-%m-%d)
# Calculate days with proper date handling
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS date command
    START_SECONDS=$(date -j -f "%Y-%m-%d" "$START_DATE" "+%s" 2>/dev/null || echo 0)
    END_SECONDS=$(date -j -f "%Y-%m-%d" "$END_DATE" "+%s" 2>/dev/null || echo 0)
    CURRENT_SECONDS=$(date "+%s")
else
    # Linux date command
    START_SECONDS=$(date -d "$START_DATE" "+%s" 2>/dev/null || echo 0)
    END_SECONDS=$(date -d "$END_DATE" "+%s" 2>/dev/null || echo 0)
    CURRENT_SECONDS=$(date "+%s")
fi
if [ $START_SECONDS -ne 0 ] && [ $END_SECONDS -ne 0 ]; then
    DAYS_ELAPSED=$(( (CURRENT_SECONDS - START_SECONDS) / 86400 ))
    DAYS_REMAINING=$(( (END_SECONDS - CURRENT_SECONDS) / 86400 ))
    TOTAL_DAYS=$(( (END_SECONDS - START_SECONDS) / 86400 ))
    # Ensure we don't divide by zero and cap progress at 100%
    if [ $TOTAL_DAYS -gt 0 ]; then
        PROGRESS=$(( DAYS_ELAPSED * 100 / TOTAL_DAYS ))
        [ $PROGRESS -gt 100 ] && PROGRESS=100
        [ $PROGRESS -lt 0 ] && PROGRESS=0
    else
        PROGRESS=0
    fi
    # Color code the remaining days
    if [ $DAYS_REMAINING -le 2 ]; then
        DAYS_COLOR=$RED
    elif [ $DAYS_REMAINING -le 5 ]; then
        DAYS_COLOR=$YELLOW
    else
        DAYS_COLOR=$GREEN
    fi
    echo "  Start Date: $START_DATE"
    echo "  Due Date: $END_DATE"
    echo "  Days Elapsed: $DAYS_ELAPSED"
    echo -e "  Days Remaining: ${DAYS_COLOR}$DAYS_REMAINING${NC}"
    echo -n "  Progress: ["
    # Progress bar
    for i in $(seq 1 20); do
        if [ $i -le $((PROGRESS / 5)) ]; then
            echo -n "█"
        else
            echo -n "░"
        fi
    done
    echo "] $PROGRESS%"
else
    echo "  Unable to calculate timeline"
fi
echo ""
# Quick health check
echo -e "${BLUE}Quick Health Check:${NC}"
HEALTH_SCORE=0
MAX_SCORE=5
# Check 1: Tests exist
if [ $(find tests -name "*Test.php" 2>/dev/null | wc -l) -gt 0 ]; then
    echo -e "  Tests: ${GREEN}✅${NC}"
    ((HEALTH_SCORE++))
else
    echo -e "  Tests: ${RED}❌ No tests found${NC}"
fi
# Check 2: Composer valid
if composer validate --quiet 2>/dev/null; then
    echo -e "  Composer: ${GREEN}✅${NC}"
    ((HEALTH_SCORE++))
else
    echo -e "  Composer: ${YELLOW}⚠️  Invalid${NC}"
fi
# Check 3: Git status clean
if [ -z "$(git status --porcelain)" ]; then
    echo -e "  Git: ${GREEN}✅ Clean${NC}"
    ((HEALTH_SCORE++))
else
    echo -e "  Git: ${YELLOW}⚠️  Uncommitted changes${NC}"
fi
# Check 4: Documentation exists
if [ -f "README.md" ]; then
    echo -e "  Documentation: ${GREEN}✅${NC}"
    ((HEALTH_SCORE++))
else
    echo -e "  Documentation: ${RED}❌ Missing README${NC}"
fi
# Check 5: CI/CD configured
if [ -f ".github/workflows/ci.yml" ] || [ -f ".github/workflows/ci.yaml" ]; then
    echo -e "  CI/CD: ${GREEN}✅${NC}"
    ((HEALTH_SCORE++))
else
    echo -e "  CI/CD: ${YELLOW}⚠️  Not configured${NC}"
fi
echo ""
echo -e "  Overall Health: $HEALTH_SCORE/$MAX_SCORE"
# Final summary
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $DAYS_REMAINING -lt 0 ]; then
    echo -e "${RED}⚠️  PROJECT OVERDUE BY ${DAYS_REMAINING#-} DAYS!${NC}"
elif [ $DAYS_REMAINING -eq 0 ]; then
    echo -e "${RED}📅 DEADLINE IS TODAY!${NC}"
elif [ $DAYS_REMAINING -le 2 ]; then
    echo -e "${YELLOW}⏰ Only $DAYS_REMAINING days remaining!${NC}"
else
    echo -e "${GREEN}📅 $DAYS_REMAINING days remaining${NC}"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"