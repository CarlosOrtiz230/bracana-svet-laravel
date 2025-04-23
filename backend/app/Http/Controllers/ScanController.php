<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
