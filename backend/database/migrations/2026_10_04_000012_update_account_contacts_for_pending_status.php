<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_contacts', function (Blueprint $table) {
            $table->dropUnique('account_contacts_active_unique_key_unique');
            $table->dropColumn('active_unique_key');

            $table->string('contact_unique_key', 300)
                ->nullable()
                ->storedAs(
                    "CASE WHEN status IN ('ACTIVE', 'PENDING') THEN CONCAT(type, ':', value) ELSE NULL END"
                );

            $table->unique('contact_unique_key');

            $table->string('pending_account_type_key', 60)
                ->nullable()
                ->storedAs(
                    "CASE WHEN status = 'PENDING' THEN CONCAT(account_id, ':', type) ELSE NULL END"
                );

            $table->unique('pending_account_type_key');
        });
    }

    public function down(): void
    {
        Schema::table('account_contacts', function (Blueprint $table) {
            $table->dropUnique('account_contacts_contact_unique_key_unique');
            $table->dropColumn('contact_unique_key');

            $table->dropUnique('account_contacts_pending_account_type_key_unique');
            $table->dropColumn('pending_account_type_key');

            $table->string('active_unique_key', 300)
                ->nullable()
                ->storedAs(
                    "CASE WHEN status = 'ACTIVE' THEN CONCAT(type, ':', value) ELSE NULL END"
                );

            $table->unique('active_unique_key');
        });
    }
};
