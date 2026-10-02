<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_sessions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('account_id', 36);
            $table->char('device_id', 36)->nullable();
            $table->string('app', 20);
            $table->string('token_hash', 255);
            $table->string('status', 20);
            $table->dateTime('created_at');
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');

            $table->foreign('device_id')
                ->references('id')
                ->on('account_devices');

            $table->index(['account_id', 'app']);
            $table->index(['account_id', 'status']);
            $table->index('device_id');

            $table->string('active_session_key', 60)
                ->nullable()
                ->storedAs("CASE WHEN status = 'ACTIVE' THEN CONCAT(account_id, ':', app) ELSE NULL END");

            $table->unique('active_session_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_sessions');
    }
};
