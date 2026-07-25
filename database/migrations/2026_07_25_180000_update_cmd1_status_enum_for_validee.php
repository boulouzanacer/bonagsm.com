<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE cmd1 MODIFY statut ENUM('en_attente','confirmee','validee','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente'");
        DB::table('cmd1')->where('statut', 'confirmee')->update(['statut' => 'validee']);
        DB::statement("ALTER TABLE cmd1 MODIFY statut ENUM('en_attente','validee','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE cmd1 MODIFY statut ENUM('en_attente','confirmee','validee','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente'");
        DB::table('cmd1')->where('statut', 'validee')->update(['statut' => 'confirmee']);
        DB::statement("ALTER TABLE cmd1 MODIFY statut ENUM('en_attente','confirmee','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente'");
    }
};
