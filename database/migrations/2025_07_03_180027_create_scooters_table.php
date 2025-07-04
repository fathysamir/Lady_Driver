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
        Schema::create('scooters', function (Blueprint $table) {
            $table->id();
            $table->string('code',191);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('motorcycle_mark_id');
            $table->foreign('motorcycle_mark_id')->references('id')->on('motorcycle_marks')->onDelete('cascade');
            $table->unsignedBigInteger('motorcycle_model_id');
            $table->foreign('motorcycle_model_id')->references('id')->on('motorcycle_models')->onDelete('cascade');
            $table->string('color')->nullable();
            $table->string('year')->nullable();
            $table->string('scooter_plate');
            $table->double('lat', 10, 6)->nullable();
            $table->double('lng', 10, 6)->nullable();
            $table->enum('status', ['pending', 'confirmed','blocked','banned'])->default('pending');
            $table->date('license_expire_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scooters');
    }
};
