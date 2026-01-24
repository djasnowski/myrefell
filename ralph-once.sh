#!/bin/bash
set -e

# Ralph Once - Single feature implementation
# Runs one iteration of feature development

MAIN_BRANCH="master"

echo "=== Ralph Once - Single Feature Implementation ==="

# Make sure we're on main branch
git checkout $MAIN_BRANCH
git pull origin $MAIN_BRANCH 2>/dev/null || true

# Run Claude to pick and implement a feature
claude --permission-mode acceptEdits -p "@PLANNING.md @progress.txt \
You are implementing features for Myrefell.

1. Read @progress.txt to find the highest-priority unimplemented task.
2. Create a new git branch: feature/<feature-name>
3. Implement the feature (models, migrations, services, controllers, pages).
4. Run tests and type checks (sail artisan test, npm run build).
5. Update @progress.txt marking what was completed.
6. Commit all changes with a descriptive message.
7. Create a PR to master using: gh pr create --title 'Feature: <name>' --body '<summary>'
8. Return to master branch.

ONLY WORK ON A SINGLE FEATURE.
If all features in @progress.txt are complete, output <promise>COMPLETE</promise>."

echo "=== Complete ==="
echo "Check open PRs with: gh pr list"
