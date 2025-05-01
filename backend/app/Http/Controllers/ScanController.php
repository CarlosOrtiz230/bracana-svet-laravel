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
    // ✅ Validate input
    $request->validate([
        'ip' => 'required|ip',
        'port' => 'required|numeric|min:1|max:65535',
    ]);

    $target = $request->ip . ':' . $request->port;
    $escapedTarget = escapeshellarg("http://$target");

    // ✅ Scan folder paths
    $niktoPath = base_path("backend/storage/app/scans/nikto");
    $zapPath = storage_path("app/scans/zap");

    // ✅ Timestamps
    $timestamp = now()->format('Y-m-d_H-i-s');

    // ✅ Log start of dynamic scan
    Log::info("Starting dynamic scan for target: $target at $timestamp");

    // ✅ Run Nikto scan
    $niktoCmd = "docker run --rm --network=host " .
                "-v \"$niktoPath:/nikto/wrk\" " .
                "-e HOST_UID=" . posix_getuid() . " -e HOST_GID=" . posix_getgid() . " " .
                "bracana-nikto $escapedTarget";

    Log::info("Running Nikto command: $niktoCmd");
    exec($niktoCmd, $niktoOutput, $niktoStatus);

    // ✅ Save Nikto output log
    $niktoLogPath = "scans/nikto/nikto_output_{$timestamp}.log";
    Storage::disk('local')->put($niktoLogPath, implode("\n", $niktoOutput));
    Log::info("Nikto scan completed. Output saved to: $niktoLogPath");

    // ✅ Run ZAP scan
    $zapCmd = "docker run --rm --network=host " .
              "-v \"$zapPath:/zap/wrk\" " .
              "bracana-zap run_zap.sh $escapedTarget";

    Log::info("Running ZAP command: $zapCmd");
    exec($zapCmd, $zapOutput, $zapStatus);

    // ✅ Save ZAP output log
    $zapLogPath = "scans/zap/zap_output_{$timestamp}.log";
    Storage::disk('local')->put($zapLogPath, implode("\n", $zapOutput));
    Log::info("ZAP scan completed. Output saved to: $zapLogPath");

    // ✅ Error feedback
    if ($niktoStatus !== 0 || $zapStatus !== 0) {
        Log::error("Dynamic scan failed for target: $target. Nikto status: $niktoStatus, ZAP status: $zapStatus");
        return back()->with('error', 'Dynamic scan failed. Check containers or target accessibility.');
    }

    Log::info("Dynamic scan for target: $target completed successfully.");
    return back()->with('success', 'Dynamic scan with Nikto and ZAP completed!');
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
