<?php

namespace App\Services\AI;

class MatchingService
{
    public function calculate($job, $application)
    {
        $resume = $application->user->resume;

        if (!$resume || !$resume->raw_ai_response) {
            return 0;
        }

        $jobText = strtolower($job->description . ' ' . $job->requirements);

        $candidateSkills = $resume->skills ?? [];
        $aiData = $resume->raw_ai_response ?? [];
        $score = 0;

        // 1. Skill Matching (50%)
        $skillScore = $this->calculateSkillMatch($jobText, $candidateSkills);
        $score += $skillScore * 0.5;

        // 2. Experience Matching (30%)
        $experienceScore = $this->calculateExperience(
            $job->experience_level,
            $aiData['experience_years'] ?? 0
        );
        $score += $experienceScore * 0.3;

        // 3. AI Resume Score (20%)
        $aiScore = $aiData['score'] ?? 50;
        $score += $aiScore * 0.2;

        return round($score);
    }

    private function calculateSkillMatch($jobText, $skills)
    {
        if (!$skills) return 0;
        $skills = json_decode($skills, true);

        $matched = 0;

        foreach ($skills as $skill) {
            if (str_contains($jobText, strtolower($skill))) {
                $matched++;
            }
        }

        return min(100, ($matched / count($skills)) * 100);
    }

    private function calculateExperience($jobLevel, $years)
    {
        $required = match ($jobLevel) {
            'internship' => 0,
            'junior' => 1,
            'mid' => 3,
            'senior' => 5,
            default => 2
        };

        if ($years >= $required) return 100;

        return ($years / max($required, 1)) * 100;
    }
}
