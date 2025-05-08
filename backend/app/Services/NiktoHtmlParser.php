<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;


class NiktoHtmlParser
{
    public static function parse(string $htmlPath): array
    {   
        Log::info("Parsing Nikto HTML report at: $htmlPath");
        $html = file_get_contents($htmlPath);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html); // suppress warnings

        $tables = $dom->getElementsByTagName('table');
        $results = [];

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            $entry = [];

            foreach ($rows as $row) {
                $cols = $row->getElementsByTagName('td');
                if ($cols->length === 2) {
                    $key = trim($cols[0]->nodeValue);
                    $value = trim($cols[1]->nodeValue);
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
        Log::info("Parsed Nikto HTML report successfully.");
        return $results;
    }
}
