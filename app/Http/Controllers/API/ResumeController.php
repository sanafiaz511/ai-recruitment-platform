<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessResumeJob;
use App\Models\Resume;
use Illuminate\Http\Request;
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

        // Create resume first
        $resume = Resume::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'extracted_text' => $text,
            'processing_status' => 'processing'
        ]);

        // Dispatch background job
        ProcessResumeJob::dispatch($resume);

        return response()->json([
            'message' => 'Resume uploaded successfully. AI analysis started.',
            'resume' => $resume
        ]);
    }

    public function myResume(Request $request)
    {
        return Resume::where('user_id', $request->user()->id)->first();
    }
}
