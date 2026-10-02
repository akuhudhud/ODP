<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_recovery_tokens', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('account_id', 36);
            $table->char('recovery_request_id', 36)->unique();
            $table->string('token_hash', 255);
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');

            $table->foreign('recovery_request_id')
                ->references('id')
                ->on('account_recovery_requests');

            $table->index('account_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_recovery_tokens');
    }
};
