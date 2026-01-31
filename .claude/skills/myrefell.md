# MyRefell Production Server

Run commands on the MyRefell production server via SSH and tinker.

## Instructions

When the user invokes `/myrefell <request>`, you should:

1. Connect to the production server via SSH: `ssh forge@myrefell.com`
2. Navigate to the application directory: `/home/forge/myrefell.com/current`
3. Execute the requested action using `php artisan tinker`

## Connection Details

- **Host**: forge@myrefell.com
- **App Directory**: /home/forge/myrefell.com/current
- **PHP Artisan**: `php artisan tinker`

## Common Tasks

### Give gold to a user
```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"\$user = App\\\\Models\\\\User::find(USER_ID); \$user->gold += AMOUNT; \$user->save(); echo 'Gold: ' . \$user->gold;\""
```

### Set energy for a user
```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"\$user = App\\\\Models\\\\User::find(USER_ID); \$user->energy = AMOUNT; \$user->save(); echo 'Energy: ' . \$user->energy;\""
```

### Find user by username
```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"\$user = App\\\\Models\\\\User::where('username', 'USERNAME')->first(); echo \$user->id . ' - ' . \$user->username . ' - ' . \$user->email;\""
```

### Give item to a user
```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"\$item = App\\\\Models\\\\Item::where('slug', 'ITEM_SLUG')->first(); App\\\\Models\\\\Inventory::updateOrCreate(['user_id' => USER_ID, 'item_id' => \$item->id], ['quantity' => \\\\DB::raw('quantity + AMOUNT')]); echo 'Done';\""
```

## Execution Format

Always use the `--execute` flag with tinker to run one-off commands:

```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"YOUR_PHP_CODE_HERE\""
```

Remember to:
- Escape backslashes properly (use `\\\\` for namespace separators in the shell command)
- Use single quotes inside the PHP code when possible
- Always echo/print the result so the user can see what happened
