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
        Schema::create('idempotency', function (Blueprint $table) {
            $table->string('key')->unique()->primary();
            $table->longText('response')->nullable();
            $table->string('hash', 64)->nullable();
            $table->timestamp('expires_at');
            $table->integer('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency');
    }
};
