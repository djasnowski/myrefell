#!/bin/bash

# Sync production database to local
# Usage: ./scripts/sync-prod-db.sh

set -e

# Production settings
PROD_HOST="forge@myrefell.com"
PROD_APP_DIR="/home/forge/myrefell.com/current"

# Local settings
LOCAL_DB_HOST="pgsql"
LOCAL_DB_NAME="myrefell"
LOCAL_DB_USER="postgres"

DUMP_FILE="/tmp/myrefell_prod_dump.sql"

echo "🔄 Syncing production database to local..."
echo ""

echo "📦 Dumping production database..."
ssh $PROD_HOST "cd $PROD_APP_DIR && source .env && PGPASSWORD=\"\$DB_PASSWORD\" pg_dump -h \$DB_HOST -U \$DB_USERNAME -d \$DB_DATABASE --no-owner --no-acl --exclude-table-data=location_activity_logs" > $DUMP_FILE 2>/dev/null
DUMP_SIZE=$(du -h $DUMP_FILE | cut -f1)
echo "   Downloaded: $DUMP_SIZE"

echo "🗑️  Dropping local database..."
./vendor/bin/sail exec -T pgsql psql -U $LOCAL_DB_USER -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$LOCAL_DB_NAME' AND pid <> pg_backend_pid();" > /dev/null
./vendor/bin/sail exec -T pgsql dropdb -U $LOCAL_DB_USER --if-exists --force $LOCAL_DB_NAME

echo "🆕 Creating fresh database..."
./vendor/bin/sail exec -T pgsql createdb -U $LOCAL_DB_USER $LOCAL_DB_NAME

echo "📤 Importing data..."
./vendor/bin/sail exec -T pgsql psql -U $LOCAL_DB_USER -d $LOCAL_DB_NAME -q < $DUMP_FILE 2>&1 | grep -v "^SET$\|^COMMENT$\|^ALTER\|^CREATE\|^REVOKE\|^GRANT" || true

rm $DUMP_FILE

echo ""
echo "✅ Sync complete!"
echo ""
echo "┌─────────────────────────────────────────┐"
echo "│           Database Stats                │"
echo "├─────────────────────┬───────────────────┤"

# Get counts for important tables
STATS=$(./vendor/bin/sail exec -T pgsql psql -U $LOCAL_DB_USER -d $LOCAL_DB_NAME -t -A -F'|' <<EOF
SELECT 'Users', COUNT(*) FROM users
UNION ALL SELECT 'Items', COUNT(*) FROM items
UNION ALL SELECT 'Player Skills', COUNT(*) FROM player_skills
UNION ALL SELECT 'Inventory', COUNT(*) FROM player_inventory
UNION ALL SELECT 'Villages', COUNT(*) FROM villages
UNION ALL SELECT 'Towns', COUNT(*) FROM towns
UNION ALL SELECT 'Kingdoms', COUNT(*) FROM kingdoms;
EOF
)

echo "$STATS" | while IFS='|' read -r name count; do
    printf "│ %-19s │ %17s │\n" "$name" "$count"
done

echo "└─────────────────────┴───────────────────┘"
