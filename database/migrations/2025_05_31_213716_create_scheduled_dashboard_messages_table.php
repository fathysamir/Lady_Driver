<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_dashboard_messages', function (Blueprint $table) {
            $table->id();
            $table->longText('message')->nullable();
            $table->json('users')->nullable();
            $table->string('receivers');
            $table->longText('image_path')->nullable();
            $table->longText('video_path')->nullable();
            $table->date('sending_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_dashboard_messages');
    }
};
