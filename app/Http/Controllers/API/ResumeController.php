<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function upload(Request $request)
    {
        $user = $request->user();

        // Only candidates
        if ($user->role !== 'candidate') {
            return response()->json([
                'message' => 'Only candidates can upload resumes'
            ], 403);
        }

        $request->validate([
            'resume' => 'required|mimes:pdf,doc,docx|max:5120'
        ]);

        $file = $request->file('resume');

        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json([
                'message' => 'File too large'
            ], 422);
        }

        $path = $file->store('resumes', 'public');

        // If resume already exists → replace
        $existing = Resume::where('user_id', $user->id)->first();

        if ($existing) {
            Storage::disk('public')->delete($existing->file_path);
            $existing->delete();
        }

        $resume = Resume::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName()
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'resume' => $resume
        ]);
    }

    public function myResume(Request $request)
    {
        return Resume::where('user_id', $request->user()->id)->first();
    }
}
