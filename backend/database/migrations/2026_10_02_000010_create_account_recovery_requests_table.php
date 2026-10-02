<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_recovery_requests', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('account_id', 36);
            $table->string('reason', 40);
            $table->string('status', 20);
            $table->dateTime('requested_at');
            $table->dateTime('reviewed_at')->nullable();
            $table->char('reviewed_by', 36)->nullable();
            $table->dateTime('recovery_expires_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');

            $table->index(['account_id', 'status']);
            $table->index('reviewed_by');
            $table->index('recovery_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_recovery_requests');
    }
};
