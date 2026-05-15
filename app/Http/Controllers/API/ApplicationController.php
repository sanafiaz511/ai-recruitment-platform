<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Services\AI\MatchingService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function apply(Request $request, MatchingService $matchingService)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:job_listings,id',
            'cover_letter' => 'nullable|string'
        ]);

        $user = $request->user();

        if ($user->role !== 'candidate') {
            return response()->json([
                'message' => 'Only candidates can apply to jobs'
            ], 403);
        }

        // Prevent duplicate application
        $existing = Application::where('user_id', $user->id)
            ->where('job_listing_id', $validated['job_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already applied for this job'
            ], 409);
        }

        // Create application
        $application = Application::create([
            'user_id' => $user->id,
            'job_listing_id' => $validated['job_id'],
            'cover_letter' => $validated['cover_letter'] ?? null,
            'status' => 'pending'
        ]);

        $job = JobListing::find($validated['job_id']);
        $score = $matchingService->calculate($job, $application);

        $application->update([
            'ai_score' => $score
        ]);

        return response()->json([
            'message' => 'Application submitted successfully',
            'ai_score' => $score,
            'application' => $application
        ]);
    }

    public function myApplications(Request $request)
    {
        return Application::with('jobListing.company')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();
    }

    public function jobApplicants(Request $request, $jobId)
    {
        $user = $request->user();

        if ($user->role !== 'recruiter') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $job = JobListing::where('id', $jobId)
            ->where('company_id', $user->company->id)
            ->first();

        if (!$job) {
            return response()->json([
                'message' => 'Job not found or unauthorized'
            ], 404);
        }

        $applications = Application::with('user')
            ->where('job_listing_id', $job->id)
            ->orderByDesc('ai_score')
            ->get();

        return response()->json($applications);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'recruiter') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,shortlisted,rejected,hired'
        ]);

        $application = Application::with('jobListing')
            ->findOrFail($id);

        // Ensure recruiter owns this job
        if ($application->jobListing->company_id !== $user->company->id) {
            return response()->json([
                'message' => 'Unauthorized access'
            ], 403);
        }

        $application->update([
            'status' => $validated['status']
        ]);

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application
        ]);
    }
}
