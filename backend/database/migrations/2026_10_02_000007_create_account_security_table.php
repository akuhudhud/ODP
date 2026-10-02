<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_security', function (Blueprint $table) {
            $table->char('account_id', 36)->primary();
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->unsignedTinyInteger('security_level')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->boolean('admin_review_required')->default(false);
            $table->dateTime('admin_reviewed_at')->nullable();
            $table->dateTime('updated_at');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');

            $table->index('security_level');
            $table->index('admin_review_required');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_security');
    }
};
