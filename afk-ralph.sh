#!/bin/bash
set -e

# AFK Ralph - Automated Feature Development
# Creates a branch for each feature, implements it, creates a PR, then moves to next

if [ -z "$1" ]; then
  echo "Usage: $0 <iterations>"
  exit 1
fi

MAIN_BRANCH="master"

for ((i=1; i<=$1; i++)); do
  echo "=== Iteration $i ==="

  # Make sure we're on main branch and up to date
  git checkout $MAIN_BRANCH
  git pull origin $MAIN_BRANCH 2>/dev/null || true

  # Run Claude to pick and implement a feature
  result=$(claude --permission-mode acceptEdits -p "@PLANNING.md @progress.txt \
  You are implementing features for Myrefell.

  1. Read @progress.txt to find the highest-priority unimplemented task.
  2. Create a new git branch: feature/<feature-name>
  3. Implement the feature (models, migrations, services, controllers, pages).
  4. Run tests and type checks (sail artisan test, npm run build).
  5. Update @progress.txt marking what was completed.
  6. Commit all changes with a descriptive message.
  7. Create a PR to master using: gh pr create --title 'Feature: <name>' --body '<summary>'
  8. Return to master branch.

  ONLY WORK ON A SINGLE FEATURE PER ITERATION.
  If all features in @progress.txt are complete, output <promise>COMPLETE</promise>.")

  echo "$result"

  if [[ "$result" == *"<promise>COMPLETE</promise>"* ]]; then
    echo "All features complete after $i iterations."
    exit 0
  fi

  echo "=== Iteration $i complete ==="
  echo ""
done

echo "Completed $1 iterations. Check open PRs with: gh pr list"
