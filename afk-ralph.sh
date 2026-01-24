#!/bin/bash
set -e

# AFK Ralph - Automated Feature Development
# Creates a branch for each feature, implements it, merges to master

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
  result=$(docker sandbox run claude --permission-mode acceptEdits -p "@PRD.md @progress.txt \
  1. Find the highest-priority unimplemented task in progress.txt. \
  2. Implement the feature (models, migrations, services, controllers, pages). \
  3. Run tests: sail artisan test && npm run build. \
  4. Update progress.txt marking what was completed. \
  5. Commit your changes with a descriptive message. \
  ONLY WORK ON A SINGLE TASK. \
  If all tasks are complete, output <promise>COMPLETE</promise>.")

  echo "$result"

  if [[ "$result" == *"<promise>COMPLETE</promise>"* ]]; then
    echo "All features complete after $i iterations."
    exit 0
  fi

  echo "=== Iteration $i complete ==="
  echo ""
done

echo "Completed $1 iterations."
