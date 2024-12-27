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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('code',191);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('car_id')->nullable();
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('cascade');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->double('total_price', 8, 2)->default(0);
            $table->double('app_rate', 8, 2)->default(0);
            $table->double('driver_rate', 8, 2)->default(0);
            $table->double('distance', 8, 2)->nullable();
            $table->double('start_lat', 10, 6)->nullable();
            $table->double('start_lng', 10, 6)->nullable();
            $table->string('address1',255)->nullable();
            $table->double('end_lat', 10, 6)->nullable();
            $table->double('end_lng', 10, 6)->nullable();
            $table->string('address2',255)->nullable();
            $table->enum('air_conditioned', ['0','1'])->default('0');
            $table->enum('animals', ['0','1'])->default('0');
            $table->float('client_stare_rate', 3, 2)->default(0);
            $table->longText('client_comment')->nullable();
            $table->enum('status', ['created','pending', 'in_progress','completed','cancelled','expired'])->default('created');
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->foreign('cancelled_by_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('trip_cancelling_reason_id')->nullable();
            $table->foreign('trip_cancelling_reason_id')->references('id')->on('trip_cancelling_reasons')->onDelete('cascade');
            $table->enum('payment_status', ['unpaid','online paid','cash paid'])->default('unpaid');
            $table->enum('type', ['individual','student','students','group'])->default('individual');
            $table->float('driver_stare_rate', 3, 2)->default(0);
            $table->longText('driver_comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
