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

        $uploadedFile = $request->file('code_file');
        $filename = time() . '_' . $uploadedFile->getClientOriginalName();
        $uploadedFile->storeAs('scans', $filename);

        $inputPath = storage_path('app/scans/' . $filename);
        $outputPath = storage_path('app/scans/report.json');

        // Run Docker container (ZAP or Nikto) - replace with your actual command
        $command = "docker run --rm -v " . dirname($inputPath) . ":/scans bracana-zap bash -c 'run_zap.sh /scans/{$filename} /scans/report.json'";
        exec($command, $output, $status);

        if ($status !== 0 || !file_exists($outputPath)) {
            return back()->with('error', 'Scan failed or output file not generated.');
        }

        $results = json_decode(file_get_contents($outputPath), true);

        return view('results', ['results' => $results]);
    }

    //Run Dynammic Analysis
    public function runDynamic(Request $request)
    {
        Log::info("based on the input go for atool to use for dynamic analyiss");
        //validate the rul
        $targetUrl = $request->input('target_url');
        $tool = $request->input('tool'); // 'zap' or 'nikto'
    
        //run the correct scan based on the toool 
        Log::info("running $tool scan on url: $targetUrl");
        return match ($tool) {
            'zap' => $this->runZapScan($targetUrl),
            'nikto' => $this->runNiktoScan($targetUrl),
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
    public function runZapScan(string $targetUrl)
    {
        Log::info("running zap scan on url: $targetUrl");
        $reportDir = storage_path('zap_reports');
        $reportFile = $reportDir . '/report.json';

        // Ensure output directory exists
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        

        // Run ZAP Docker container
        $cmd = "docker run --rm " .
            "--user " . posix_getuid() . ":" . posix_getgid() . " " .
            "-v " . escapeshellarg($reportDir) . ":/zap/wrk " .
            "zap-scanner run_zap.sh " . escapeshellarg($targetUrl);

        exec($cmd, $output, $status);

        if ($status !== 0 || !file_exists($reportFile)) {
            return response()->json([
                'error' => 'ZAP scan failed or no output generated.'
            ], 500);
        }
     
        
        $alerts = $parsed['site'][0]['alerts'] ?? [];

        // Save to database
        $scan = ZapScan::create([
            'target_url' => $targetUrl,
            'findings' => $alerts,
            'raw_output' => $rawJson,
        ]);

        return view('result', [
            'results' => $alerts,
            'tool' => 'zap',
            'scan_id' => $scan->id,
        ]);
    }


}
