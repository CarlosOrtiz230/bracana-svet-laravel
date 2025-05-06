<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MetricsController extends Controller
{
    public function analyzeNiktoHtml(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        $filename = $request->input('filename');
        $fullPath = storage_path("app/scans/nikto/$filename");

        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTMLFile($fullPath);
        libxml_clear_errors();

        $tables = $doc->getElementsByTagName('table');
        $vulns = [];

        foreach ($tables as $table) {
            if ($table->getAttribute('class') !== 'dataTable') continue;

            $rows = $table->getElementsByTagName('tr');
            $entry = [];
            foreach ($rows as $row) {
                $cells = $row->getElementsByTagName('td');
                if ($cells->length === 2) {
                    $key = trim($cells[0]->nodeValue);
                    $val = trim($cells[1]->nodeValue);
                    $entry[$key] = $val;
                }
            }

            if (isset($entry['URI'], $entry['Description'])) {
                $entry['Severity'] = $this->categorizeSeverity($entry['Description']);
                $vulns[] = $entry;
            }
        }

        return response()->json([
            'total_findings' => count($vulns),
            'metrics' => $this->groupBySeverity($vulns),
            'details' => $vulns
        ]);
    }

    private function categorizeSeverity($description)
    {
        $desc = strtolower($description);
        if (str_contains($desc, 'xss') || str_contains($desc, 'injection')) {
            return 'High';
        } elseif (str_contains($desc, 'information disclosure') || str_contains($desc, 'headers missing')) {
            return 'Medium';
        } elseif (str_contains($desc, 'interesting') || str_contains($desc, 'not set')) {
            return 'Low';
        }
        return 'Info';
    }

    private function groupBySeverity($vulns)
    {
        $counts = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0, 'Info' => 0];
        foreach ($vulns as $v) {
            $severity = $v['Severity'] ?? 'Info';
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            } else {
                $counts['Info']++;
            }
        }
        return $counts;
    }
}
