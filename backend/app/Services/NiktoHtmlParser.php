<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NiktoHtmlParser
{
    /**
     * Parses a Nikto HTML report.
     * 
     * @param string $htmlPath
     * @return array [$findings, $targetUrl]
     */
    public static function parse(string $htmlPath): array
    {
        Log::info("Parsing Nikto HTML report at: $htmlPath");

        $html = file_get_contents($htmlPath);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html); // Suppress malformed HTML warnings

        $tables = $dom->getElementsByTagName('table');
        $results = [];
        $targetUrl = 'unknown';

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            $entry = [];

            foreach ($rows as $row) {
                $cols = $row->getElementsByTagName('td');
                if ($cols->length === 2) {
                    $key = trim($cols[0]->nodeValue);
                    $value = trim($cols[1]->nodeValue);

                    // Extract Site Link (Name) as target_url
                    if ($key === 'Site Link (Name)' && preg_match('/href="([^"]+)"/', $cols[1]->ownerDocument->saveHTML($cols[1]), $matches)) {
                        $targetUrl = $matches[1];
                    }

                    $entry[$key] = $value;
                }
            }

            if (!empty($entry['Description'])) {
                $results[] = [
                    'uri' => $entry['URI'] ?? '',
                    'method' => $entry['HTTP Method'] ?? '',
                    'description' => $entry['Description'],
                    'references' => $entry['References'] ?? '',
                ];
            }
        }

        Log::info("Parsed Nikto HTML report. Findings: " . count($results) . ", Target: $targetUrl");

        return [$results, $targetUrl];
    }
}
