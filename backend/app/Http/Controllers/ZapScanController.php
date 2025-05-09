<?php

namespace App\Http\Controllers;

use App\Models\ZapScan;
use Illuminate\Http\Request;

class ZapScanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_url' => 'required|url',
            'findings' => 'nullable|array',
            'raw_output' => 'nullable|string',
        ]);

        $scan = ZapScan::create($validated);

        return response()->json([
            'message' => 'Scan stored',
            'scan_id' => $scan->id
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ZapScan $zapScan)
    {   
        Log::info('ZapScanController@show', ['zapScan' => $zapScan]);
        $scan = ZapScan::findOrFail($id);
        return response()->json($scan);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ZapScan $zapScan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ZapScan $zapScan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ZapScan $zapScan)
    {
        //
    }
}
