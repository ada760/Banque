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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Référence vers User PostgreSQL
            $table->string('recipient_phone')->nullable();
            $table->enum('type', ['transfer', 'payment']); // transfer, payment
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending'); // pending, success, failed
            $table->string('reference')->nullable(); // Pour factures
            $table->string('merchant_id')->nullable(); // ID du marchand
            $table->string('service_id')->nullable(); // ID du service
            $table->json('qr_metadata')->nullable(); // Métadonnées QR code
            $table->decimal('fee', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamp('transaction_date');
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            $table->index('transaction_date');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'transaction_date']);

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
