<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SemgrepScan;

class SemgrepScanController extends Controller
{
    public function store(Request $request)
{
    $scan = SemgrepScan::create([
        'target_url' => $request->input('target_url', 'uploaded-file'),
        'findings' => $request->input('findings', []),
        'raw_output' => $request->input('raw_output', ''),
    ]);

    return response()->json($scan);
}
}
