<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScanController extends Controller
{
    public function index()
    {
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


    public function runDynamic(Request $request)
    {
        $targetUrl = $request->input('target_url');
        $tool = $request->input('tool'); // 'zap' or 'nikto'
    
        return match ($tool) {
            'zap' => $this->runZapScan($targetUrl),
            'nikto' => $this->runNiktoScan($targetUrl),
            default => response()->json(['error' => 'Invalid scan tool'], 400),
        };
    }
    

    

public function runStatic(Request $request)
{
    // ✅ Validate uploaded file type
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


}
