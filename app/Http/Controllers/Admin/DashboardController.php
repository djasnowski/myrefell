<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private AdminAnalyticsService $analytics) {}

    /**
     * Display the admin dashboard with analytics.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->analytics->getDashboardStats(),
            'registrationTrend' => $this->analytics->getRegistrationTrend(30),
            'activeUsersTrend' => $this->analytics->getActiveUsersTrend(30),
            'recentActivity' => $this->analytics->getRecentGlobalActivity(20),
            'latestUsers' => $this->analytics->getLatestRegisteredUsers(10),
            'topByGold' => $this->analytics->getTopPlayersByGold(10),
        ]);
    }
}
