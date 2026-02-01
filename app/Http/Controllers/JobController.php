<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\EmploymentJob;
use App\Models\Kingdom;
use App\Models\PlayerEmployment;
use App\Models\Town;
use App\Models\Village;
use App\Services\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function __construct(
        protected JobService $jobService
    ) {}

    /**
     * Display jobs available at a village.
     */
    public function villageJobs(Request $request, Village $village): Response
    {
        $user = $request->user();

        // Check if player is at this village
        if ($user->current_location_type !== 'village' || $user->current_location_id !== $village->id) {
            return Inertia::render('Jobs/NotHere', [
                'location' => $village->name,
            ]);
        }

        return $this->renderJobsPage($user, 'village', $village->id, $village->name);
    }

    /**
     * Display jobs available at a barony.
     */
    public function baronyJobs(Request $request, Barony $barony): Response
    {
        $user = $request->user();

        // Check if player is at this barony
        if ($user->current_location_type !== 'barony' || $user->current_location_id !== $barony->id) {
            return Inertia::render('Jobs/NotHere', [
                'location' => $barony->name,
            ]);
        }

        return $this->renderJobsPage($user, 'barony', $barony->id, $barony->name);
    }

    /**
     * Display jobs available at a town.
     */
    public function townJobs(Request $request, Town $town): Response
    {
        $user = $request->user();

        // Check if player is at this town
        if ($user->current_location_type !== 'town' || $user->current_location_id !== $town->id) {
            return Inertia::render('Jobs/NotHere', [
                'location' => $town->name,
            ]);
        }

        return $this->renderJobsPage($user, 'town', $town->id, $town->name);
    }

    /**
     * Display jobs available at a duchy.
     */
    public function duchyJobs(Request $request, Duchy $duchy): Response
    {
        $user = $request->user();

        // Check if player is at this duchy
        if ($user->current_location_type !== 'duchy' || $user->current_location_id !== $duchy->id) {
            return Inertia::render('Jobs/NotHere', [
                'location' => $duchy->name,
            ]);
        }

        return $this->renderJobsPage($user, 'duchy', $duchy->id, $duchy->name);
    }

    /**
     * Display jobs available at a kingdom.
     */
    public function kingdomJobs(Request $request, Kingdom $kingdom): Response
    {
        $user = $request->user();

        // Check if player is at this kingdom
        if ($user->current_location_type !== 'kingdom' || $user->current_location_id !== $kingdom->id) {
            return Inertia::render('Jobs/NotHere', [
                'location' => $kingdom->name,
            ]);
        }

        return $this->renderJobsPage($user, 'kingdom', $kingdom->id, $kingdom->name);
    }

    /**
     * Render the jobs page for any location type.
     */
    protected function renderJobsPage($user, string $locationType, int $locationId, string $locationName): Response
    {
        $availableJobs = $this->jobService->getAvailableJobs($user, $locationType, $locationId);
        $currentEmployment = $this->jobService->getEmploymentAtLocation($user, $locationType, $locationId);
        $allEmployment = $this->jobService->getCurrentEmployment($user);
        $isSettled = $this->jobService->isSettledAt($user, $locationType, $locationId);

        return Inertia::render('Jobs/Index', [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'available_jobs' => $availableJobs,
            'current_employment' => $currentEmployment,
            'all_employment' => $allEmployment,
            'max_jobs' => JobService::MAX_CONCURRENT_JOBS,
            'is_settled' => $isSettled,
            'player' => [
                'energy' => $user->energy,
                'max_energy' => $user->max_energy,
                'gold' => $user->gold,
            ],
        ]);
    }

    /**
     * Apply for a job.
     */
    public function apply(Request $request): RedirectResponse
    {
        $request->validate([
            'job_id' => 'required|exists:employment_jobs,id',
            'location_type' => 'required|in:village,town,barony,duchy,kingdom',
            'location_id' => 'required|integer',
        ]);

        $user = $request->user();
        $job = EmploymentJob::findOrFail($request->job_id);

        $result = $this->jobService->applyForJob(
            $user,
            $job,
            $request->location_type,
            $request->location_id
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Work at a job.
     */
    public function work(Request $request, PlayerEmployment $employment): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $result = $this->jobService->work($user, $employment);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Quit a job.
     */
    public function quit(Request $request, PlayerEmployment $employment): RedirectResponse
    {
        $user = $request->user();
        $result = $this->jobService->quitJob($user, $employment);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Get current employment status (for sidebar/API).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $employment = $this->jobService->getCurrentEmployment($user);

        return response()->json([
            'employment' => $employment,
            'job_count' => $employment->count(),
            'max_jobs' => JobService::MAX_CONCURRENT_JOBS,
        ]);
    }

    /**
     * Fire a worker (supervisor action).
     */
    public function fire(Request $request, PlayerEmployment $employment): RedirectResponse|JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $supervisor = $request->user();
        $result = $this->jobService->fireWorker($supervisor, $employment, $request->reason);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Get workers supervised by the current user at a location.
     */
    public function supervisedWorkers(Request $request): JsonResponse
    {
        $request->validate([
            'location_type' => 'required|in:village,town,barony,duchy,kingdom',
            'location_id' => 'required|integer',
        ]);

        $supervisor = $request->user();
        $workers = $this->jobService->getSupervisedWorkers(
            $supervisor,
            $request->location_type,
            $request->location_id
        );

        return response()->json([
            'workers' => $workers,
            'count' => $workers->count(),
        ]);
    }
}
