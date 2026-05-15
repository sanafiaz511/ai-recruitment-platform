<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use App\Services\AI\ResumeService;

class ResumeController extends Controller
{
    public function upload(Request $request, ResumeService $aiService)
    {
        $user = $request->user();

        if ($user->role !== 'candidate') {
            return response()->json([
                'message' => 'Only candidates allowed'
            ], 403);
        }

        $request->validate([
            'resume' => 'required|mimes:pdf,doc,docx|max:5120'
        ]);

        $file = $request->file('resume');
        $path = $file->store('resumes', 'public');

        // Extract text
        $text = '';

        if ($file->getClientOriginalExtension() === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();
        }

        if (in_array($file->getClientOriginalExtension(), ['doc', 'docx'])) {
            $phpWord = IOFactory::load($file->getPathname());
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . ' ';
                    }
                }
            }
        }

        // AI analysis
        $aiResult = $aiService->analyze($text);

        $parsed = $aiResult;

        $resume = Resume::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),

            'parsed_data' => json_encode($parsed),

            'skills' => json_encode($parsed['skills'] ?? []),
            'experience_years' => $parsed['experience_years'] ?? null,

            'strengths' => json_encode($parsed['strengths'] ?? []),
            'weaknesses' => json_encode($parsed['weaknesses'] ?? []),

            'summary' => $parsed['summary'] ?? null,
            'score' => $parsed['score'] ?? null,

            'raw_ai_response' => json_encode($aiResult),
        ]);

        return response()->json([
            'message' => 'Resume uploaded & analyzed successfully',
            'resume' => $resume
        ]);
    }

    public function myResume(Request $request)
    {
        return Resume::where('user_id', $request->user()->id)->first();
    }
}
