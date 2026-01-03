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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('code', 191);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('car_mark_id');
            $table->foreign('car_mark_id')->references('id')->on('car_marks')->onDelete('cascade');
            $table->unsignedBigInteger('car_model_id');
            $table->foreign('car_model_id')->references('id')->on('car_models')->onDelete('cascade');
            $table->string('color')->nullable();
            $table->string('year')->nullable();
            $table->string('car_plate');
            $table->double('lat', 10, 6)->nullable();
            $table->double('lng', 10, 6)->nullable();
            $table->enum('passenger_type', ['female', 'male_female'])->default('female');
            $table->enum('air_conditioned', ['0', '1'])->default('0');
            $table->enum('animals', ['0', '1'])->default('0');
            $table->enum('status', ['pending', 'confirmed', 'blocked', 'banned'])->default('pending');
            $table->date('license_expire_date');
            $table->enum('is_comfort', ['0', '1'])->default('0');
            $table->date('car_inspection_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
