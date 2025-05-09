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
        Schema::create('semgrep_scans', function (Blueprint $table) {
            $table->id();
            $table->string('target_file');
            $table->json('findings')->nullable();
            $table->longText('raw_output')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semgrep_scans');
    }
};
