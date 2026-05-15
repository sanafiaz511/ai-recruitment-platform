<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function index()
    {
        return response()->json(JobListing::with('company')->latest()->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Only recruiters
        if ($user->role !== 'recruiter') {
            return response()->json([
                'message' => 'Only recruiters can post jobs'
            ], 403);
        }

        // Recruiter must have company
        if (!$user->company) {
            return response()->json([
                'message' => 'Create company profile first'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'type' => 'required',
        ]);

        $job = JobListing::create([
            'company_id' => $user->company->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'],
            'type' => $validated['type'],
        ]);

        return response()->json($job);
    }
}
