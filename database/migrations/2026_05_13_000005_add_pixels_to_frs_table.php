<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->string('meta_pixel_id', 50)->nullable()->after('enable_frais_livraison');
            $table->string('tiktok_pixel_id', 50)->nullable()->after('meta_pixel_id');
        });
    }

    public function down(): void
    {
        Schema::table('frs', function (Blueprint $table) {
            $table->dropColumn(['meta_pixel_id', 'tiktok_pixel_id']);
        });
    }
};

