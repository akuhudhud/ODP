<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_contacts', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('account_id', 36);
            $table->string('type', 20);
            $table->string('value', 255);
            $table->string('status', 20);
            $table->boolean('is_verified');
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            // ACTIVE contact mesti unik secara global.
            $table->string('active_unique_key', 300)
                ->nullable()
                ->storedAs("CASE WHEN status = 'ACTIVE' THEN CONCAT(type, ':', value) ELSE NULL END");

            // Satu Account hanya boleh mempunyai satu ACTIVE contact bagi setiap type.
            $table->string('active_account_type_key', 60)
                ->nullable()
                ->storedAs("CASE WHEN status = 'ACTIVE' THEN CONCAT(account_id, ':', type) ELSE NULL END");

            $table->unique('active_unique_key');
            $table->unique('active_account_type_key');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');

            $table->index(['account_id', 'type']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_contacts');
    }
};
