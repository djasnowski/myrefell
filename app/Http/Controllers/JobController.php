<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\EmploymentJob;
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
     * Render the jobs page for any location type.
     */
    protected function renderJobsPage($user, string $locationType, int $locationId, string $locationName): Response
    {
        $availableJobs = $this->jobService->getAvailableJobs($user, $locationType, $locationId);
        $currentEmployment = $this->jobService->getEmploymentAtLocation($user, $locationType, $locationId);
        $allEmployment = $this->jobService->getCurrentEmployment($user);

        return Inertia::render('Jobs/Index', [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'available_jobs' => $availableJobs,
            'current_employment' => $currentEmployment,
            'all_employment' => $allEmployment,
            'max_jobs' => JobService::MAX_CONCURRENT_JOBS,
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
            'location_type' => 'required|in:village,barony,town',
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
}
