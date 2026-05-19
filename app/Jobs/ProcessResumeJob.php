<?php

namespace App\Jobs;

use App\Models\Resume;
use App\Services\AI\ResumeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessResumeJob implements ShouldQueue
{
    use Queueable;

    public $resume;

    public function __construct(Resume $resume)
    {
        $this->resume = $resume;
    }

    public function handle(ResumeService $aiService): void
    {
        $resume = $this->resume;

        $text = $resume->extracted_text;

        if (!$text) {
            return;
        }

        $aiResult = $aiService->analyze($text);

        $decoded = json_decode($aiResult, true);

        $resume->update([
            'parsed_data' => $aiResult,
            'skills' => $decoded['skills'] ?? [],
            'experience_years' => $decoded['experience_years'] ?? 0,
            'strengths' => $decoded['strengths'] ?? [],
            'weaknesses' => $decoded['weaknesses'] ?? [],
            'summary' => $decoded['summary'] ?? null,
            'score' => $decoded['score'] ?? 0,
            'raw_ai_response' => $aiResult,
            'processing_status' => 'completed'
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->resume->update([
            'processing_status' => 'failed'
        ]);
    }
}
