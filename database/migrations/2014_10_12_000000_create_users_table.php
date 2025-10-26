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
            $table->string('email');
            $table->string('phone')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'blocked', 'banned'])->default('pending');
            $table->string('OTP')->nullable();
            $table->enum('is_online', ['0', '1'])->default('0');
            $table->enum('is_verified', ['0', '1'])->default('0');
            $table->enum('mode', ['client', 'driver', 'admin'])->default('client');
            $table->enum('gendor', ['Male', 'Female', 'other'])->default('Female');
            $table->string('national_id')->unique()->nullable();
            $table->string('passport_id')->unique()->nullable();
            $table->double('lat', 10, 6)->nullable();
            $table->double('lng', 10, 6)->nullable();
            $table->longText('address')->nullable();
            $table->string('invitation_code')->nullable();
            $table->date('birth_date')->nullable();
            $table->longText('device_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('password2')->nullable();
            $table->enum('seen', ['0', '1'])->default('0');
            $table->decimal('wallet', 15, 2)->default(0);
            $table->string('theme')->nullable();
            $table->string('country_code')->nullable();
            $table->enum('level', ['0', '1', '2', '3', '4', '5'])->default('0');
            $table->string('student_code')->nullable();
            $table->enum('driver_type', ['car', 'comfort_car', 'scooter'])->nullable();

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
