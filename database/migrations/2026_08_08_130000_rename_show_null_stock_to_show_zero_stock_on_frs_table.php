<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->tinyInteger('show_zero_stock')->default(1)->after('show_null_stock');
        });

        DB::table('frs')->update([
            'show_zero_stock' => DB::raw('show_null_stock'),
        ]);

        Schema::table('frs', function (Blueprint $table) {
            $table->dropColumn('show_null_stock');
        });
    }

    public function down(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->tinyInteger('show_null_stock')->default(1)->after('show_zero_stock');
        });

        DB::table('frs')->update([
            'show_null_stock' => DB::raw('show_zero_stock'),
        ]);

        Schema::table('frs', function (Blueprint $table) {
            $table->dropColumn('show_zero_stock');
        });
    }
};
