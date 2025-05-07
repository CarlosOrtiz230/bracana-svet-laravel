<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MetricsController extends Controller
{
    protected array $severityScores = [
        'informational' => 0,
        'low' => 1,
        'medium' => 3,
        'high' => 5,
    ];

    public function analyze(Request $request)
    {
        $results = $request->input('results'); // expects a JSON array
        $tool = $request->input('tool');

        $score = 0;
        $toolBreakdown = [
            'zap' => 0,
            'nikto' => 0,
            'semgrep' => 0,
            'codeql' => 0,
        ];
        $severityBreakdown = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
        ];

        foreach ($results as $finding) {
            $severity = $this->extractSeverity($finding, $tool);
            $score += $this->severityScores[$severity] ?? 0;

            $toolBreakdown[$tool] += $this->severityScores[$severity] ?? 0;
            if (isset($severityBreakdown[$severity])) {
                $severityBreakdown[$severity]++;
            }
        }

        return response()->json([
            'total_score' => $score,
            'by_tool' => $toolBreakdown,
            'by_severity' => $severityBreakdown,
        ]);
    }

    protected function extractSeverity(array $item, string $tool): string
    {
        return match ($tool) {
            'zap' => strtolower(trim(strtok($item['riskdesc'] ?? 'unknown', ' '))),
            'nikto' => $this->guessNiktoSeverity($item),
            'semgrep' => strtolower($item['severity'] ?? 'low'),
            'codeql' => strtolower($item['severity'] ?? 'warning'),
            default => 'informational'
        };
    }

    protected function guessNiktoSeverity(array $item): string
    {
        // crude example: use keywords or OSVDB tag
        $text = strtolower($item['msg'] ?? '');
        return str_contains($text, 'critical') || str_contains($text, 'remote') ? 'high' :
               (str_contains($text, 'insecure') || str_contains($text, 'exposed') ? 'medium' : 'low');
    }
}
