<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->tinyInteger('admin_approval_required')->default(0)->after('show_zero_stock');
        });

        Schema::table('client', function (Blueprint $table) {
            $table->tinyInteger('approved_by_admin')->default(1)->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->dropColumn('admin_approval_required');
        });

        Schema::table('client', function (Blueprint $table) {
            $table->dropColumn('approved_by_admin');
        });
    }
};
