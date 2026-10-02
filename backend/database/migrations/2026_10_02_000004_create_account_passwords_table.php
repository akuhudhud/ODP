<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_passwords', function (Blueprint $table) {
            $table->char('account_id', 36)->primary();
            $table->string('password_hash', 255);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_passwords');
    }
};
