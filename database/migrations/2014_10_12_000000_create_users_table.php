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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique()->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->enum('status', ['pending', 'confirmed','blocked'])->default('pending');
            $table->string('OTP')->nullable();
            $table->enum('is_online', ['0', '1'])->default('1');
            $table->enum('mode', ['client', 'driver'])->default('client');
            $table->string('national_id')->unique()->nullable();
            $table->double('lat', 10, 6)->nullable();
            $table->double('lng', 10, 6)->nullable();
            $table->longText('address')->nullable();
            $table->string('invitation_code')->nullable();
            $table->date('birth_date')->nullable();
            $table->longText('device_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('theme')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
