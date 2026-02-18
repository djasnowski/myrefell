---
name: ban
description: Investigate a player and generate a ban report with evidence
---

# Player Ban Investigation

Investigate a player's account using the built-in suspicious activity detection and generate a ban report.

## Instructions

When the user invokes `/ban <username>`, you should:

1. Look up the player on production
2. Pull their suspicious activity stats (already tracked by the system)
3. Present findings in a structured report
4. Ask for confirmation before banning

## Investigation Steps

### Step 1: Basic Account Info + Suspicious Activity Stats
```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"
\\\$u = App\\\\Models\\\\User::where('username', 'USERNAME')->first();
if (!\\\$u) { echo 'User not found'; exit; }

echo '=== ACCOUNT INFO ===' . PHP_EOL;
echo 'ID: ' . \\\$u->id . PHP_EOL;
echo 'Username: ' . \\\$u->username . PHP_EOL;
echo 'Email: ' . \\\$u->email . PHP_EOL;
echo 'Created: ' . \\\$u->created_at . PHP_EOL;
echo 'Account Age: ' . \\\$u->created_at->diffInDays(now()) . ' days' . PHP_EOL;
echo 'Gold: ' . number_format(\\\$u->gold) . PHP_EOL;
echo 'Total Level: ' . \\\$u->total_level . PHP_EOL;
echo 'Combat Level: ' . \\\$u->combat_level . PHP_EOL;
echo 'Is Banned: ' . (\\\$u->banned_at ? 'Yes (since ' . \\\$u->banned_at . ')' : 'No') . PHP_EOL;
echo 'Flagged At: ' . (\\\$u->suspicious_activity_flagged_at ?? 'Never') . PHP_EOL;
echo PHP_EOL;

echo '=== SUSPICIOUS ACTIVITY (Last 24h) ===' . PHP_EOL;
\\\$stats = App\\\\Models\\\\TabActivityLog::getSuspiciousActivity(\\\$u->id, now()->subDay()->toDateTimeString());
echo 'Suspicious %: ' . \\\$stats['suspicious_percentage'] . '%' . PHP_EOL;
echo 'Total Requests: ' . number_format(\\\$stats['total_requests']) . PHP_EOL;
echo 'Requests/Hour: ' . \\\$stats['requests_per_hour'] . PHP_EOL;
echo 'Max Actions/Second: ' . \\\$stats['max_actions_per_second'] . PHP_EOL;
echo 'Same-Tab Bot Seconds: ' . \\\$stats['same_tab_bot_seconds'] . PHP_EOL;
echo 'Multi-Tab Rapid Actions: ' . \\\$stats['rapid_xp_actions'] . PHP_EOL;
echo 'Unique Tabs Used: ' . \\\$stats['unique_tabs'] . PHP_EOL;
echo 'Tab Switches: ' . \\\$stats['new_tab_switches'] . PHP_EOL;
echo PHP_EOL;

echo '=== ALL-TIME SUSPICIOUS ACTIVITY ===' . PHP_EOL;
\\\$allTime = App\\\\Models\\\\TabActivityLog::getSuspiciousActivity(\\\$u->id);
echo 'Suspicious %: ' . \\\$allTime['suspicious_percentage'] . '%' . PHP_EOL;
echo 'Total Requests: ' . number_format(\\\$allTime['total_requests']) . PHP_EOL;
echo 'Max Actions/Second: ' . \\\$allTime['max_actions_per_second'] . PHP_EOL;
echo 'Same-Tab Bot Seconds: ' . \\\$allTime['same_tab_bot_seconds'] . PHP_EOL;
echo 'Multi-Tab Rapid Actions: ' . \\\$allTime['rapid_xp_actions'] . PHP_EOL;
\""
```

### Step 2: Check for Alt Accounts (Same IP)
```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"
\\\$u = App\\\\Models\\\\User::where('username', 'USERNAME')->first();
\\\$sameIp = App\\\\Models\\\\User::where('last_login_ip', \\\$u->last_login_ip)->where('id', '!=', \\\$u->id)->pluck('username');
echo 'Last Login IP: ' . \\\$u->last_login_ip . PHP_EOL;
echo 'Other accounts from same IP: ' . (\\\$sameIp->isEmpty() ? 'None' : \\\$sameIp->join(', '));
\""
```

## Report Format

Present findings like this:

```
## Ban Investigation Report: [Username]

### Account Summary
- **User ID**:
- **Email**:
- **Account Age**: X days
- **Flagged At**: [date or Never]

### Suspicious Activity (24h)
- **Suspicious %**: X%
- **Max Actions/Second**: X (normal is 1-2, bots often 5+)
- **Same-Tab Bot Seconds**: X
- **Multi-Tab Rapid Actions**: X

### All-Time Stats
- **Suspicious %**: X%
- **Total Requests**: X
- **Max Actions/Second**: X

### Alt Accounts
- [list or None]

### Verdict
**[BOTTING CONFIRMED / SUSPICIOUS / CLEAR]**

Evidence: [summarize key indicators]
```

## Interpreting the Stats

- **Suspicious %**: >20% is concerning, >50% is almost certainly botting
- **Max Actions/Second**: Normal humans can't exceed 2-3. Bots often hit 5-10+
- **Same-Tab Bot Seconds**: Seconds where >2 XP actions happened in same tab (autoclicker)
- **Multi-Tab Rapid Actions**: XP actions across different tabs within 3 seconds (multi-tabbing exploit)

## Executing the Ban

Only after user confirmation. This creates a `UserBan` record and sets `banned_at` on the user:
```bash
ssh forge@myrefell.com "cd /home/forge/myrefell.com/current && php artisan tinker --execute=\"
\\\$u = App\\\\Models\\\\User::where('username', 'USERNAME')->first();
\\\$u->banned_at = now();
\\\$u->save();
App\\\\Models\\\\UserBan::create(['user_id' => \\\$u->id, 'banned_by' => 1, 'reason' => 'Botting/automation detected - X% suspicious activity, Y max actions per second', 'banned_at' => now()]);
\\\$removed = \\\$u->activePlayerRoles;
foreach (\\\$removed as \\\$r) { \\\$r->update(['status' => App\\\\Models\\\\PlayerRole::STATUS_REMOVED, 'removed_at' => now()]); }
echo 'Banned ' . \\\$u->username . ' at ' . \\\$u->banned_at . PHP_EOL;
echo 'Removed ' . \\\$removed->count() . ' active role(s).' . PHP_EOL;
\""
```

The `banned_by` value of `1` is the admin user (dan). The `reason` field should summarize the key evidence. Any active roles (e.g. Elder, Mayor, Guildmaster) are automatically removed on ban.

## Important Notes

- The system already tracks suspicious activity via `TabActivityLog`
- Users are auto-flagged when suspicious patterns are detected
- Always present evidence clearly before banning
- Never ban without explicit confirmation
