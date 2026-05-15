<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'nullable|url',
            'description' => 'nullable|string'
        ]);

        $user = $request->user();

        if ($user->role !== 'recruiter') {
            return response()->json([
                'message' => 'Only recruiters can create companies'
            ], 403);
        }

        // Prevent duplicate company
        if ($user->company) {
            return response()->json([
                'message' => 'Company already exists'
            ], 409);
        }

        $company = Company::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'website' => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null
        ]);

        return response()->json([
            'message' => 'Company created successfully',
            'company' => $company
        ]);
    }

    public function myCompany(Request $request)
    {
        return response()->json([
            'company' => $request->user()->company
        ]);
    }

    public function update(Request $request)
    {
        $company = $request->user()->company;

        if (!$company) {
            return response()->json([
                'message' => 'Company not found'
            ], 404);
        }

        if ($request->user()->role !== 'recruiter') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'website' => 'nullable|url',
            'description' => 'nullable|string'
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company
        ]);
    }
}
