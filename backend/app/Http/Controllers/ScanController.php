<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ZapScan;


 

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
        // Validate uploaded file type
        $request->validate([
            'code_file' => 'required|file|mimes:py,java,js',
        ]);

        $file = $request->file('code_file');
        $filename = $file->getClientOriginalName();
        $filepath = $file->storeAs('scans/semgrep', $filename);

        // ✅ Use full path for Docker volume
        $hostPath = storage_path("app/scans/semgrep");

        // ✅ Log the start of the static scan
        Log::info("Starting static scan for file: $filename");

        // ✅ Run Semgrep in Docker
        $cmd = "docker run --rm -v " .
            escapeshellarg($hostPath) . ":/src " .
            "returntocorp/semgrep semgrep --config=auto /src/$filename -o /src/semgrep_result_$filename.json --json";

        Log::info("Running Semgrep command: $cmd");
        exec($cmd, $output, $status);

        if ($status !== 0) {
            Log::error("Static scan failed for file: $filename. Command status: $status");
            return back()->with('error', 'Static scan failed to run.');
        }

        // ✅ Log the completion of the static scan
        $resultPath = "scans/semgrep/semgrep_result_$filename.json";
        Log::info("Static scan completed successfully for file: $filename. Results saved to: $resultPath");

        return back()->with('success', 'Static scan completed!');
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
        // $cmd = "docker run --rm " .
        //     "--user " . posix_getuid() . ":" . posix_getgid() . " " .
        //     "-v " . escapeshellarg($reportDir) . ":/zap/wrk " .
        //     "zap-scanner run_zap.sh " . escapeshellarg($targetUrl) . " " . escapeshellarg($complexity);

        $cmd .= " > /dev/null 2>&1 &";
        exec($cmd);
        Log::info("ZAP scan command sent to background.");



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
                'results' => $alerts,
                'tool' => 'zap',
                'scan_id' => $scan->id,
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


}
