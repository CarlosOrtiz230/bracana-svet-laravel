<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_create_zap_scans_table.php

    public function up()
    {
        Schema::create('zap_scans', function (Blueprint $table) {
            $table->id();
            $table->string('target_url');
            $table->json('findings')->nullable(); // stores parsed alert objects
            $table->text('raw_output')->nullable(); // optional: full JSON dump
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zap_scans');
    }
};
