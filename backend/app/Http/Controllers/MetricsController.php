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

        foreach ($results as &$finding) { // Use reference to modify in-place
            $severity = $this->extractSeverity($finding, $tool);
            $score += $this->severityScores[$severity] ?? 0;
        
            $toolBreakdown[$tool] += $this->severityScores[$severity] ?? 0;
            if (isset($severityBreakdown[$severity])) {
                $severityBreakdown[$severity]++;
            }
        
            // ✅ Inject severity and alert title for UI to pick up
            if (!isset($finding['severity'])) {
                $finding['severity'] = $severity;
            }
        
            if (!isset($finding['alert']) && isset($finding['description'])) {
                $finding['alert'] = ucfirst(substr($finding['description'], 0, 60)) . '...';
            }
        }
        

        return response()->json([
            'total_score' => $score,
            'by_tool' => $toolBreakdown,
            'by_severity' => $severityBreakdown,
            'results' => $results,  
        ]);
        
    }

    protected function extractSeverity(array $item, string $tool): string
    {
        return match ($tool) {
            'zap' => strtolower(trim(strtok($item['riskdesc'] ?? 'unknown', ' '))),
            'nikto' => $this->guessNiktoSeverity($item),
           'semgrep' => $this->normalizeSemgrepSeverity($item['severity'] ?? 'INFO'),
            'codeql' => strtolower($item['severity'] ?? 'warning'),
            default => 'informational'
        };
    }

    public function guessNiktoSeverity(array $item): string
    {
        $description = strtolower($item['description'] ?? '');

        // High-risk indicators
        if (str_contains($description, 'remote file inclusion') ||
            str_contains($description, 'directory traversal') ||
            str_contains($description, 'admin login found') ||
            str_contains($description, 'authentication bypass')) {
            return 'high';
        }

        // Medium-risk indicators
        if (str_contains($description, 'x-content-type-options') ||
            str_contains($description, 'strict-transport-security') ||
            str_contains($description, 'csp') || // content-security-policy
            str_contains($description, 'referrer-policy') ||
            str_contains($description, 'methods') || // allowed http methods
            str_contains($description, 'banner changed') ||
            str_contains($description, 'clickjacking') ||
            str_contains($description, 'access-control-allow-origin')) {
            return 'medium';
        }

        // Anything else = low
        return 'low';
    }

    protected function normalizeSemgrepSeverity(string $raw): string
    {
        return match (strtoupper($raw)) {
            'ERROR' => 'high',
            'WARNING' => 'medium',
            'INFO' => 'low',
            default => 'informational'
        };
    }

    //Reusable shortcut for inline usage  for scoring
    public function analyzeInline(array $results, string $tool): int
    {
        $score = 0;
        foreach ($results as $finding) {
            $severity = $this->extractSeverity($finding, $tool);
            $score += $this->severityScores[$severity] ?? 0;
        }
        return $score;
    }


}
