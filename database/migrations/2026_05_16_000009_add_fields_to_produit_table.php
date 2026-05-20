<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            $table->unsignedBigInteger('id_categorie')->nullable()->after('categorie');
            $table->unsignedBigInteger('id_sous_categorie')->nullable()->after('id_categorie');
            $table->unsignedBigInteger('id_marque')->nullable()->after('id_sous_categorie');

            $table->foreign('id_categorie')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('id_sous_categorie')->references('id')->on('sous_categories')->onDelete('set null');
            $table->foreign('id_marque')->references('id')->on('marques')->onDelete('set null');
        });

        // Migrate existing categorie string to id_categorie
        $prods = DB::table('produit')->select('id', 'id_frs', 'categorie')->get();
        foreach ($prods as $p) {
            if ($p->categorie) {
                $catId = DB::table('categories')
                    ->where('id_frs', $p->id_frs)
                    ->where('nom', $p->categorie)
                    ->value('id');
                
                if ($catId) {
                    DB::table('produit')->where('id', $p->id)->update(['id_categorie' => $catId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            $table->dropForeign(['id_marque']);
            $table->dropForeign(['id_sous_categorie']);
            $table->dropForeign(['id_categorie']);
            $table->dropColumn(['id_categorie', 'id_sous_categorie', 'id_marque']);
        });
    }
};
