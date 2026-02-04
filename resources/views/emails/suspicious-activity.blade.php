<x-mail::message>
# Suspicious Activity Detected

A user has been flagged for potential multi-tab abuse.

**User:** {{ $user->username }} (ID: {{ $user->id }})
**Email:** {{ $user->email }}

## Activity Stats (Last 24 Hours)

- **Total Requests:** {{ number_format($stats['total_requests']) }}
- **Tab Switches:** {{ number_format($stats['new_tab_switches']) }}
- **Unique Tabs:** {{ $stats['unique_tabs'] }}
- **Suspicious Percentage:** {{ $stats['suspicious_percentage'] }}%

<x-mail::button :url="config('app.url') . '/admin/users/' . $user->id">
View User Profile
</x-mail::button>

<x-mail::button :url="config('app.url') . '/admin/suspicious-activity'">
View All Flagged Users
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
