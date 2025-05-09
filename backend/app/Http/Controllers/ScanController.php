<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\ZapScan;
use App\Models\NiktoScan;
use App\Services\NiktoHtmlParser;
use Illuminate\Support\Facades\DB;
use App\Models\SemgrepScan; 

class ScanController extends Controller
{
    public function index()
    {
        Log::info('view upload');
        return view('upload');
    }

    public function scan(Request $request)
    {
        $request->validate([
            'code_file' => 'required|file'
        ]);

        $request->validate([
            'code_file' => 'required|file|mimes:py,java,js',
        ]);
        
        $complexity = $request->input('complexity'); // <-- added line
        Log::info("Static scan complexity level: $complexity");

        $uploadedFile = $request->file('code_file');
        $filename = time() . '_' . $uploadedFile->getClientOriginalName();
        $uploadedFile->storeAs('scans', $filename);

        $inputPath = storage_path('app/scans/' . $filename);
        $outputPath = storage_path('app/scans/report.json');

        // Run Docker container (ZAP or Nikto) - replace with your actual command
        $command = "docker run --rm -v " . dirname($inputPath) . ":/scans bracana-zap bash -c 'run_zap.sh /scans/{$filename} /scans/report.json'";
        exec($command, $output, $status);

        Log::debug("ZAP Output: " . implode("\n", $output));
        Log::debug("Checking for file: $reportFile");
        Log::debug("File exists? " . (file_exists($reportFile) ? 'yes' : 'no'));


        if (!file_exists($reportFile)) {
            Log::error("ZAP report file not found at: $reportFile");
            return response()->json(['error' => 'Report not generated.'], 500);
        }
        

        $results = json_decode(file_get_contents($outputPath), true);

        return view('results', ['results' => $results]);
    }

    //Run Dynammic Analysis
    public function runDynamic(Request $request)
{
    Log::info("based on the input go for a tool to use for dynamic analysis");

    $targetUrl = $request->input('target_url');
    $tool = $request->input('tool'); // ✅ add this line
 
    $complexity = $request->input('complexity', 'medium'); // fallback default
    Log::info("Dynamic scan complexity level: $complexity");

    
    $hostMode = env('DOCKER_HOST_MODE', 'linux');

    Log::info("Inputs received - Target URL: $targetUrl, Tool: $tool, Complexity: $complexity, Host Mode: $hostMode");


    //helped by the env variable to determin the host mode
    //there are colitions with 127.0.1 since the container may get confused with itself even specifying the port
    
    if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1)/', $targetUrl)) {
        switch ($hostMode) {
            case 'mac':
            case 'windows':
                $targetUrl = str_replace(['localhost', '127.0.0.1'], 'host.docker.internal', $targetUrl);
                break;
            case 'linux':
            default:
                $targetUrl = str_replace(['localhost', '127.0.0.1'], '172.17.0.1', $targetUrl);
                break;
        }
    }

    Log::info("running $tool scan on url: $targetUrl");

    return match ($tool) {
        'zap' => $this->runZapScan($targetUrl, $complexity),
        'nikto' => $this->runNiktoScan($targetUrl, $complexity),
        default => response()->json(['error' => 'Invalid scan tool'], 400),
    };
}

    
    // Run Static Analyisi
    public function runStatic(Request $request)
    {
        $tool = $request->input('tool');
        $complexity = $request->input('complexity', 'medium');
        //open to add more tools
        Log::info("Running static analysis with tool: $tool, complexity: $complexity");

        return match ($tool) {
            'semgrep' => $this->runSemgrep($request, $complexity),
            default => back()->with('error', 'Unsupported static analysis tool.'),
        };
    }


    
   
    //Run Analyisis with Owasp Zap based on a provided URL
    public function runZapScan(string $targetUrl, string $complexity = 'medium')
    {
        Log::info("running zap scan on url: $targetUrl");
        $reportDir = storage_path('zap_reports');
        $reportFile = $reportDir . '/report.json';

        // Ensure output directory exists
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        
        Log::info("creating cmd command");
        // Run ZAP Docker container
        $cmd = "docker run --rm " .
            "--user " . posix_getuid() . ":" . posix_getgid() . " " .
            "-v {$reportDir}:/zap/wrk " .
            "zap-scanner run_zap.sh {$targetUrl} {$complexity}";

         
    



        Log::info("Running ZAP command: $cmd");

        exec($cmd, $output, $status);

        Log::info("Finished ZAP command. Status: $status");
        Log::debug("ZAP Output: " . implode("\n", $output));



        if ($status !== 0 || !file_exists($reportFile)) {
            return response()->json([
                'error' => 'ZAP scan failed or no output generated.'
            ], 500);
        }
        
        
        $rawJson = file_get_contents($reportFile);

        if (!$rawJson) {
            Log::error("ZAP report is empty or unreadable.");
            return response()->json(['error' => 'Empty report'], 500);
        }

        $parsed = json_decode($rawJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON parsing failed: " . json_last_error_msg());
            return response()->json(['error' => 'Invalid report format'], 500);
        }
        
        $alerts = $parsed['site'][0]['alerts'] ?? [];

        Log::info("Creating ZapScan record");
        // Save to database
        $scan = ZapScan::create([
            'target_url' => $targetUrl,
            'findings' => $alerts,
            'raw_output' => $rawJson,
        ]);

        if (!$scan) {
            Log::error("Failed to create ZapScan record");
            return response()->json(['error' => 'Database insert failed'], 500);
        }


        Log::info("ZAP scan completed successfully. Scan ID: " . $scan->id);

        try {
            Log::info("Attempting to render result view with alerts: " . count($alerts));

            // DEBUG: Dump the first alert to ensure data is present and correctly structured

             Log::debug("First alert: " . json_encode($alerts[0] ?? 'No alerts'));

             return view('results', [
                'results' => $analysis['results'],
                'tool' => 'nikto',
                'scan_id' => $scan->id,
                'total_score' => $analysis['total_score'],
            ]);
        } catch (\Throwable $e) {
            Log::error("Error rendering result view: " . $e->getMessage());
           return response()->json([
                'error' => 'Failed to render results',
                'exception' => $e->getMessage(),
                'alerts_sample' => array_slice($alerts, 0, 1),
            ], 500);
        }

    }


    public function runNiktoScan(string $targetUrl, string $complexity = 'medium')
    {
        Log::info("Starting Nikto scan on URL: $targetUrl with complexity: $complexity");

        $reportDir = storage_path('nikto_reports');
        Log::debug("Report directory: $reportDir");

        if (!is_dir($reportDir)) {
            Log::info("Report directory does not exist. Creating directory: $reportDir");
            mkdir($reportDir, 0755, true);
        }

        $timestamp = date("Y-m-d_H-i-s");
        $jsonReport = "$reportDir/nikto_report_{$timestamp}.json";
        $htmlReport = "$reportDir/nikto_report_{$timestamp}.html";

        Log::debug("Generated report paths - JSON: $jsonReport, HTML: $htmlReport");

        // $cmd = "docker run --rm " .
        //     "--user " . posix_getuid() . ":" . posix_getgid() . " " .
        //     "-e HOST_UID=" . posix_getuid() . " -e HOST_GID=" . posix_getgid() . " " .
        //     "-v {$reportDir}:/nikto/wrk " .
        //     "bracana-nikto {$targetUrl}";



        $cmd = "docker run --rm " .
            "-v {$reportDir}:/nikto/wrk " .
            "bracana-nikto {$targetUrl}";

        Log::info("Creating Nikto command " .   $cmd);

       
        exec($cmd, $output, $status);

        Log::info("Nikto command execution completed. Status: $status");
        Log::debug("Nikto command output: " . implode("\n", $output));

        if ($status !== 0 || !file_exists($htmlReport)) {
            Log::error("Nikto scan failed or HTML report not found. Status: $status, HTML Report Path: $htmlReport");
            return response()->json(['error' => 'Nikto scan failed.'], 500);
        }

        Log::info("Parsing HTML report: $htmlReport");
        $parsedFindings = NiktoHtmlParser::parse($htmlReport);

        if (empty($parsedFindings)) {
            Log::warning("Parsed findings are empty. Check the HTML report for issues.");
        } else {
            Log::info("Parsed findings successfully. Total findings: " . count($parsedFindings));
        }

        $rawJson = file_exists($jsonReport) ? file_get_contents($jsonReport) : '';
        if ($rawJson) {
            Log::info("Raw JSON report found and loaded: $jsonReport");
        } else {
            Log::warning("Raw JSON report not found. Falling back to parsed HTML findings.");
        }

        Log::info("Saving Nikto scan results to the database.");
        $scan = NiktoScan::create([
            'target_url' => $targetUrl,
            'findings' => $parsedFindings,
            'raw_output' => $rawJson ?: 'No JSON available. Parsed from HTML.',
        ]);

        if ($scan) {
            Log::info("Nikto scan saved successfully. Scan ID: " . $scan->id);
        } else {
            Log::error("Failed to save Nikto scan to the database.");
            return response()->json(['error' => 'Database insert failed.'], 500);
        }

        Log::info("Rendering results view for Nikto scan. Scan ID: " . $scan->id);

        // Normalize the results for better display
        $results = $this->normalizeNiktoResults($parsedFindings); // from NiktoHtmlParser or DB
        return view('results', [
            'results' => $analysis['results'],
            'tool' => 'nikto',
            'scan_id' => $scan->id,
            'total_score' => $analysis['total_score'],
        ]);
        
    }


    

    public function history()
    {
        $zapScans = ZapScan::orderBy('created_at', 'desc')->get();
        $niktoScans = NiktoScan::orderBy('created_at', 'desc')->get();
        $semgrepScans = SemgrepScan::orderBy('created_at', 'desc')->get(); // ✅ Add this

        return view('history', compact('zapScans', 'niktoScans', 'semgrepScans')); // ✅ Include
    }


    public function recoverStoredReports()
    {
        $niktoDir = storage_path('nikto_reports');
        $zapDir = storage_path('zap_reports');
    
        // ✅ Nikto HTML files
        foreach (File::files($niktoDir) as $file) {
            if (str_ends_with($file, '.html')) {
                // 👇 Assume parse() returns [$findings, $target]
                [$parsedFindings, $targetUrl] = NiktoHtmlParser::parse($file->getPathname());
                    $rawLabel = 'Recovered from file: ' . $file->getFilename();
    
                $existing =  NiktoScan::where('raw_output', $rawLabel)->first();
                if (!$existing) {
                     NiktoScan::create([

                        'target_url' => $targetUrl ?: 'unknown',
                        'findings' => $parsedFindings,
                        'raw_output' => $rawLabel,
                    ]);
                }
            }
        }
    
        // ✅ ZAP JSON files
        foreach (File::files($zapDir) as $file) {
            if (str_ends_with($file, '.json')) {
                $raw = File::get($file);
                $parsed = json_decode($raw, true);
                $alerts = $parsed['site'][0]['alerts'] ?? [];
                $target = $parsed['site'][0]['@name'] ?? 'unknown';
    
                $existing =  ZapScan::where('raw_output', $raw)->first();
                if (!$existing) {
                    ZapScan::create([
                        'target_url' => $target,
                        'findings' => $alerts,
                        'raw_output' => $raw,
                    ]);
                }
            }
        }
    
        return back()->with('success', 'Recovered stored scans successfully.');
    }
    
    private function normalizeNiktoResults(array $results): array
    {
        $normalized = [];

        foreach ($results as $item) {
            $description = $item['msg'] ?? $item['description'] ?? '';
            $uri = $item['url'] ?? $item['uri'] ?? '';
            $method = $item['method'] ?? '';
            $reference = $item['references'] ?? '';

            // Guess severity
            $severity = $this->guessNiktoSeverity($description);

            $normalized[] = [
                'alert' => ucfirst(strtok($description, '.')) . '.', // first sentence as title
                'severity' => $severity,
                'description' => $description,
                'uri' => $uri,
                'method' => $method,
                'references' => $reference,
            ];
        }

        return $normalized;
    }

    public function showNiktoResults($id)
    {
        $scan = NiktoScan::findOrFail($id);

        // Normalize the stored findings
        $normalized = $this->normalizeNiktoResults($scan->findings ?? []);

        return view('results', [
            'results' => $normalized,
            'tool' => 'nikto',
            'scan_id' => $scan->id,
        ]);
    }

    private function guessNiktoSeverity(string $description): string
    {
        $description = strtolower($description);
    
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
            str_contains($description, 'csp') ||
            str_contains($description, 'referrer-policy') ||
            str_contains($description, 'methods') ||
            str_contains($description, 'banner changed') ||
            str_contains($description, 'clickjacking') ||
            str_contains($description, 'access-control-allow-origin')) {
            return 'medium';
        }
    
        // Default to low
        return 'low';
    }
       
    public function runSemgrep(Request $request, string $complexity)
{
    $request->validate([
        'code_file' => 'required|file|mimes:py,java,js',
    ]);

    $file = $request->file('code_file');
    $filename = time() . '_' . $file->getClientOriginalName();

    $hostPath = storage_path("semgrep_reports");
    if (!is_dir($hostPath)) {
        mkdir($hostPath, 0755, true);
    }

    // ✅ Move the uploaded file into the folder that will be mounted into Docker
    $fullFilePath = $hostPath . DIRECTORY_SEPARATOR . $filename;
    $file->move($hostPath, $filename);

    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $outputPath = "$hostPath/semgrep_result_{$baseName}.json";

    $cmd = "docker run --rm -v {$hostPath}:/src " .
           "bracana-semgrep /usr/local/bin/run_semgrep.sh /src/{$filename} {$complexity} /src";

    Log::info("Running Semgrep: $cmd");
    exec($cmd, $output, $status);

    if (!file_exists($outputPath)) {
        Log::error("Semgrep report not found at: $outputPath");
        return back()->with('error', 'Semgrep scan failed to generate output.');
    }

    $raw = file_get_contents($outputPath);
    $parsed = json_decode($raw, true);

    if (!empty($parsed['errors'])) {
        Log::error("Semgrep scan errors found", ['errors' => $parsed['errors']]);
        return back()->with('error', 'Semgrep scan failed: ' . ($parsed['errors'][0]['message'] ?? 'Unknown error.'));
    }

    $results = $parsed['results'] ?? [];
    Log::info("Semgrep findings parsed: " . count($results));

    $normalized = array_map(function ($item) {
        return [
            'alert' => ucfirst(strtok($item['extra']['message'], '.')) . '.',
            'severity' => strtolower($item['severity'] ?? 'low'),
            'description' => $item['extra']['message'] ?? '',
            'uri' => $item['path'] ?? '',
            'method' => '',
            'references' => implode(', ', $item['extra']['metadata']['references'] ?? []),
        ];
    }, $results);

    $scan = \App\Models\SemgrepScan::create([
        'target_file' => $filename,
        'findings' => $normalized,
        'raw_output' => $raw,
    ]);

    if (!$scan) {
        Log::error("Failed to save Semgrep scan.");
        return back()->with('error', 'Database insert failed.');
    }

    $totalScore = app(MetricsController::class)->analyzeInline($normalized, 'semgrep');

    return view('results', [
        'results' => $normalized,
        'tool' => 'semgrep',
        'scan_id' => $scan->id,
        'total_score' => $totalScore,
    ]);
}





}
