<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    /**
     * Get total number of users.
     */
    public function getTotalUsers(): int
    {
        return User::count();
    }

    /**
     * Get number of active users in the last N days.
     */
    public function getActiveUsers(int $days = 7): int
    {
        return User::where('updated_at', '>=', now()->subDays($days))->count();
    }

    /**
     * Get number of new users registered today.
     */
    public function getNewUsersToday(): int
    {
        return User::whereDate('created_at', today())->count();
    }

    /**
     * Get number of currently banned users.
     */
    public function getBannedUsersCount(): int
    {
        return User::whereNotNull('banned_at')->count();
    }

    /**
     * Get number of admin users.
     */
    public function getAdminUsersCount(): int
    {
        return User::where('is_admin', true)->count();
    }

    /**
     * Get user registration trend for the past N days.
     *
     * @return Collection<int, array{date: string, count: int}>
     */
    public function getRegistrationTrend(int $days = 30): Collection
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $registrations = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $result->push([
                'date' => $date,
                'count' => $registrations->get($date)?->count ?? 0,
            ]);
        }

        return $result;
    }

    /**
     * Get active users trend for the past N days.
     *
     * @return Collection<int, array{date: string, count: int}>
     */
    public function getActiveUsersTrend(int $days = 30): Collection
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $activeUsers = User::select(
            DB::raw('DATE(updated_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('updated_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $result->push([
                'date' => $date,
                'count' => $activeUsers->get($date)?->count ?? 0,
            ]);
        }

        return $result;
    }

    /**
     * Get all dashboard stats as an array.
     *
     * @return array{
     *     totalUsers: int,
     *     activeUsers: int,
     *     newUsersToday: int,
     *     bannedUsers: int,
     *     adminUsers: int
     * }
     */
    public function getDashboardStats(): array
    {
        return [
            'totalUsers' => $this->getTotalUsers(),
            'activeUsers' => $this->getActiveUsers(),
            'newUsersToday' => $this->getNewUsersToday(),
            'bannedUsers' => $this->getBannedUsersCount(),
            'adminUsers' => $this->getAdminUsersCount(),
        ];
    }
}
