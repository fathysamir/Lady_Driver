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
        Schema::create('trip_cancelling_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('ar_reason');
            $table->string('en_reason');
            $table->enum('type', ['client','driver','all'])->default('client');
            $table->enum('status', ['before','driver_arrived','after'])->default('before');
            $table->enum('value_type', ['fixed', 'ratio'])->default('fixed');
            $table->double('value', 4, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_cancelling_reasons');
    }
};
