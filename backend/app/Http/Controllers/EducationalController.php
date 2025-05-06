<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class EducationalController extends Controller
{
    public function explainNiktoFindings(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        $filename = $request->input('filename');
        $path = storage_path("app/scans/nikto/{$filename}");

        if (!file_exists($path)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        $content = file_get_contents($path);
        $parsed = json_decode($content, true);

        if (!is_array($parsed)) {
            return response()->json(['error' => 'Invalid JSON structure.'], 400);
        }

        $explanations = [];

        foreach ($parsed as $issue) {
            $description = $issue['description'] ?? $issue['msg'] ?? $issue['title'] ?? 'Unknown issue';

            $prompt = "Explain the following web vulnerability in simple terms and suggest how to fix it: \"$description\"";

            try {
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a security assistant that gives educational explanations about web vulnerabilities.'],
                        ['role' => 'user', 'content' => $prompt]
                    ]
                ]);

                $responseText = $response['choices'][0]['message']['content'] ?? 'No explanation available.';
            } catch (\Exception $e) {
                Log::error("OpenAI error: " . $e->getMessage());
                $responseText = 'Error generating explanation.';
            }

            $explanations[] = [
                'original' => $description,
                'explanation' => $responseText
            ];
        }

        return view('educational_results', ['feedback' => $explanations]);
    }
}
