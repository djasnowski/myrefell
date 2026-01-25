<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Display the calendar/world time page.
     */
    public function index(): Response
    {
        return Inertia::render('Calendar/Index', [
            'calendar' => $this->calendarService->getCalendarData(),
        ]);
    }
}
