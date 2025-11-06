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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['epargne', 'cheque']);
            $table->string('num_compte')->unique();
            $table->string('devise');
            $table->foreignUuid('client_id')->constrained('clients');
            $table->enum('status', ['bloque','actif', 'ferme', 'suspendu']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
