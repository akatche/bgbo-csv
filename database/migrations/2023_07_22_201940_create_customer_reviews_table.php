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
        Schema::create('customer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('batch_id')->nullable()->constrained(table: 'job_batches');
            $table->string('transaction_type');
            $table->dateTime('date');
            $table->string('customer_number');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('sent_type')->nullable();
            $table->boolean('sent')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->string('reason')->nullable();
            $table->json('original_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_reviews');
    }
};
