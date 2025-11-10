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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('code_secret_om')->nullable()->after('telephone'); // Code secret OM (4 chiffres, hashé)
            $table->integer('otp_attempts')->default(0)->after('code_secret_om'); // Tentatives OTP échouées
            $table->timestamp('last_otp_request')->nullable()->after('otp_attempts'); // Dernière demande OTP
            $table->timestamp('blocked_until')->nullable()->after('last_otp_request'); // Blocage temporaire
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['code_secret_om', 'otp_attempts', 'last_otp_request', 'blocked_until']);
        });
    }
};
