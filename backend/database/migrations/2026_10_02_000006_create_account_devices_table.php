<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_devices', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('account_id', 36);
            $table->string('device_identifier', 255);
            $table->string('platform', 50)->nullable();
            $table->string('device_name', 255)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts');

            $table->index('account_id');
            $table->index('device_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_devices');
    }
};
