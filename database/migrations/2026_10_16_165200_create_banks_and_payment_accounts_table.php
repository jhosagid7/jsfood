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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej. Banesco Pago Móvil, Bancolombia, Nequi, Zelle Chase, Binance Pay USDT
            $table->string('type')->default('bank_transfer'); // 'pago_movil', 'bank_transfer', 'zelle', 'binance', 'nequi', 'card', 'cash'
            $table->string('account_holder')->nullable(); // Nombre del Titular
            $table->string('account_number')->nullable(); // Número de cuenta, correo o Pay ID
            $table->string('cedula')->nullable(); // C.I. / RIF / NIT
            $table->string('phone')->nullable(); // Teléfono asociado
            $table->string('currency_code', 10)->default('USD'); // VED, USD, COP, USDT
            $table->boolean('state')->default(true); // Activo / Inactivo
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->after('user_id')->constrained('banks')->nullOnDelete();
            $table->string('proof_image')->nullable()->after('reference'); // Ruta del capture / comprobante
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn(['bank_id', 'proof_image']);
        });

        Schema::dropIfExists('banks');
    }
};
