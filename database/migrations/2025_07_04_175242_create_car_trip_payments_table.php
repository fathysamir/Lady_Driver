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
        Schema::create('car_trip_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->enum('method', ['cash', 'wallet', 'paymob', 'fawry', 'paypal']);
            $table->timestamp('payment_date')->nullable();
            $table->string('transaction_id')->nullable(); // from Paymob/Fawry/PayPal
            $table->string('status')->default('completed');
            $table->longText('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_trip_payments');
    }
};
