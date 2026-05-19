<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query =  JobListing::with('company')->where('is_active', true);

        if ($request->keyword) {

            $keyword = strtolower($request->keyword);

            $query->where(function ($q) use ($keyword) {

                $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(requirements) LIKE ?', ["%{$keyword}%"]);
            });
        }

        // Location Filter
        if ($request->location) {
            $query->where('location', $request->location);
        }

        // Job Type Filter
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Experience Filter
        if ($request->experience_level) {
            $query->where('experience_level', $request->experience_level);
        }

        return $query->latest()->paginate(10);
    }

    public function show($slug)
    {
        $job = JobListing::with('company')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($job);
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

    public function recommended(Request $request)
    {
        $user = $request->user();

        if (!$user->resume) {
            return response()->json([]);
        }

        $skills = $user->resume->skills ?? [];
        $skills = json_decode($skills, true);
        
        $query = JobListing::query();

        foreach ($skills as $skill) {

            $query->orWhereRaw(
                'LOWER(description) LIKE ?',
                ['%' . strtolower($skill) . '%']
            );
        }

        return $query->latest()->take(10)->get();
    }
}
