<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_stats', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('cache_status');
            $table->integer('response_time_ms');
            $table->string('user_session_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_stats');
    }
};