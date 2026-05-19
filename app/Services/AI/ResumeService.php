<?php

namespace App\Services\AI;

use GuzzleHttp\Client;

class ResumeService
{
    public function analyze($text)
    {
        $client = new Client();

        $apiKey = env('GEMINI_API_KEY');

        $prompt = "
        You are a strict JSON generator.

        RULES:
        - Return ONLY valid JSON
        - No markdown
        - No explanation
        - No text before or after JSON

        FORMAT:
        {
        \"score\": 0,
        \"skills\": [],
        \"experience_years\": 0,
        \"strengths\": [],
        \"weaknesses\": [],
        \"summary\": \"\"
        }

        Resume:
        {$text}
        ";

        $response = $client->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}",
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $apiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $aiText = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        $aiText = trim(str_replace(["```json", "```"], "", $aiText));

        $parsed = json_decode($aiText, true);

        return $parsed;
    }
}
