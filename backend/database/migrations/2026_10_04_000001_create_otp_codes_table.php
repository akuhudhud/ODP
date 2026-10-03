<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->char('id', 36)->primary();

            $table->char('account_id', 36);

            $table->char('contact_id', 36);

            $table->string('purpose', 30);

            $table->string('channel', 20);

            $table->string('code_hash', 255);

            $table->string('status', 20);

            $table->unsignedTinyInteger('attempts');

            $table->dateTime('expires_at');

            $table->dateTime('last_sent_at');

            $table->dateTime('verified_at')->nullable();

            $table->dateTime('invalidated_at')->nullable();

            $table->dateTime('created_at');

            $table->dateTime('updated_at');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');

            $table->foreign('contact_id')
                ->references('id')
                ->on('account_contacts');

            $table->index([
                'account_id',
                'purpose',
                'status',
            ]);

            $table->index([
                'contact_id',
                'purpose',
                'status',
            ]);

            $table->index([
                'expires_at',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
