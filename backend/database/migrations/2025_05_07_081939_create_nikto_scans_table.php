<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('nikto_scans', function (Blueprint $table) {
        $table->id();
        $table->string('target_url');
        $table->json('findings')->nullable();  // Store parsed vulnerabilities
        $table->longText('raw_output')->nullable(); // Raw JSON or text output
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nikto_scans');
    }
};
