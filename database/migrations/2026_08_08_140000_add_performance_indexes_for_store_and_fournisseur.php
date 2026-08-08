<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            $names = $this->indexNames('produit', [
                'idx_produit_frs_actif_stock',
                'idx_produit_frs_actif_cat',
                'idx_produit_frs_actif_ref',
                'idx_produit_frs_actif_c_date',
            ]);

            $this->safeAddIndex($table, 'idx_produit_frs_actif_stock',
                ['id_frs', 'actif', 'deleted_at', 'stock'], $names);
            $this->safeAddIndex($table, 'idx_produit_frs_actif_cat',
                ['id_frs', 'actif', 'deleted_at', 'categorie'], $names);
            $this->safeAddIndex($table, 'idx_produit_frs_actif_ref',
                ['id_frs', 'actif', 'deleted_at', 'reference'], $names);
            $this->safeAddIndex($table, 'idx_produit_frs_actif_c_date',
                ['id_frs', 'actif', 'deleted_at', 'created_at'], $names);
        });

        Schema::table('client_wishlist', function (Blueprint $table) {
            $names = $this->indexNames('client_wishlist', [
                'idx_wishlist_client_produit',
            ]);
            $this->safeAddIndex($table, 'idx_wishlist_client_produit',
                ['id_client', 'id_produit'], $names);
        });

        Schema::table('frais_livraison', function (Blueprint $table) {
            $names = $this->indexNames('frais_livraison', [
                'idx_frais_livraison_frs_wilaya',
            ]);
            $this->safeAddIndex($table, 'idx_frais_livraison_frs_wilaya',
                ['id_frs', 'id_wilaya'], $names, true);
        });

        Schema::table('cmd1', function (Blueprint $table) {
            $names = $this->indexNames('cmd1', [
                'idx_cmd1_frs_statut_date',
                'idx_cmd1_client_frs_date',
            ]);
            $this->safeAddIndex($table, 'idx_cmd1_frs_statut_date',
                ['id_frs', 'statut', 'date_cmd'], $names);
            $this->safeAddIndex($table, 'idx_cmd1_client_frs_date',
                ['id_client', 'id_frs', 'date_cmd'], $names);
        });

        Schema::table('cmd2', function (Blueprint $table) {
            $names = $this->indexNames('cmd2', [
                'idx_cmd2_cmd_produit',
            ]);
            $this->safeAddIndex($table, 'idx_cmd2_cmd_produit',
                ['id_cmd', 'id_produit'], $names);
        });

        Schema::table('categories', function (Blueprint $table) {
            $names = $this->indexNames('categories', [
                'idx_categories_frs_nom',
            ]);
            $this->safeAddIndex($table, 'idx_categories_frs_nom',
                ['id_frs', 'nom'], $names, true);
        });

        Schema::table('sous_categories', function (Blueprint $table) {
            $names = $this->indexNames('sous_categories', [
                'idx_sous_categories_frs_cat_nom',
            ]);
            $this->safeAddIndex($table, 'idx_sous_categories_frs_cat_nom',
                ['id_frs', 'id_categorie', 'nom'], $names, true);
        });

        Schema::table('marques', function (Blueprint $table) {
            $names = $this->indexNames('marques', [
                'idx_marques_frs_nom',
            ]);
            $this->safeAddIndex($table, 'idx_marques_frs_nom',
                ['id_frs', 'nom'], $names, true);
        });

        Schema::table('produit_images', function (Blueprint $table) {
            $names = $this->indexNames('produit_images', [
                'idx_produit_images_produit_ordre',
            ]);
            $this->safeAddIndex($table, 'idx_produit_images_produit_ordre',
                ['id_produit', 'ordre'], $names);
        });

        Schema::table('client', function (Blueprint $table) {
            $names = $this->indexNames('client', [
                'idx_client_frs_telephone',
                'idx_client_frs_type_tarif',
            ]);
            $this->safeAddIndex($table, 'idx_client_frs_telephone',
                ['id_frs', 'telephone'], $names);
            $this->safeAddIndex($table, 'idx_client_frs_type_tarif',
                ['id_frs', 'type_client', 'tarif'], $names);
        });

        Schema::table('commune', function (Blueprint $table) {
            $names = $this->indexNames('commune', [
                'idx_commune_wilaya_nom',
            ]);
            $this->safeAddIndex($table, 'idx_commune_wilaya_nom',
                ['ID_WILAYA', 'COMMUNE'], $names);
        });
    }

    public function down(): void
    {
        Schema::table('produit', function (Blueprint $table) {
            foreach ([
                'idx_produit_frs_actif_stock',
                'idx_produit_frs_actif_cat',
                'idx_produit_frs_actif_ref',
                'idx_produit_frs_actif_c_date',
            ] as $idx) {
                if ($this->indexExists('produit', $idx)) {
                    $table->dropIndex($idx);
                }
            }
        });

        Schema::table('client_wishlist', function (Blueprint $table) {
            if ($this->indexExists('client_wishlist', 'idx_wishlist_client_produit')) {
                $table->dropIndex('idx_wishlist_client_produit');
            }
        });

        Schema::table('frais_livraison', function (Blueprint $table) {
            if ($this->indexExists('frais_livraison', 'idx_frais_livraison_frs_wilaya')) {
                $table->dropUnique('idx_frais_livraison_frs_wilaya');
            }
        });

        Schema::table('cmd1', function (Blueprint $table) {
            foreach ([
                'idx_cmd1_frs_statut_date',
                'idx_cmd1_client_frs_date',
            ] as $idx) {
                if ($this->indexExists('cmd1', $idx)) {
                    $table->dropIndex($idx);
                }
            }
        });

        Schema::table('cmd2', function (Blueprint $table) {
            if ($this->indexExists('cmd2', 'idx_cmd2_cmd_produit')) {
                $table->dropIndex('idx_cmd2_cmd_produit');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if ($this->indexExists('categories', 'idx_categories_frs_nom')) {
                $table->dropUnique('idx_categories_frs_nom');
            }
        });

        Schema::table('sous_categories', function (Blueprint $table) {
            if ($this->indexExists('sous_categories', 'idx_sous_categories_frs_cat_nom')) {
                $table->dropUnique('idx_sous_categories_frs_cat_nom');
            }
        });

        Schema::table('marques', function (Blueprint $table) {
            if ($this->indexExists('marques', 'idx_marques_frs_nom')) {
                $table->dropUnique('idx_marques_frs_nom');
            }
        });

        Schema::table('produit_images', function (Blueprint $table) {
            if ($this->indexExists('produit_images', 'idx_produit_images_produit_ordre')) {
                $table->dropIndex('idx_produit_images_produit_ordre');
            }
        });

        Schema::table('client', function (Blueprint $table) {
            foreach ([
                'idx_client_frs_telephone',
                'idx_client_frs_type_tarif',
            ] as $idx) {
                if ($this->indexExists('client', $idx)) {
                    $table->dropIndex($idx);
                }
            }
        });

        Schema::table('commune', function (Blueprint $table) {
            if ($this->indexExists('commune', 'idx_commune_wilaya_nom')) {
                $table->dropIndex('idx_commune_wilaya_nom');
            }
        });
    }

    /**
     * @param string $table
     * @param array<int, string> $wanted
     * @return array<string, string> key = wanted name, value = actual MySQL index name (possibly same)
     */
    private function indexNames(string $table, array $wanted): array
    {
        try {
            $rows = DB::select('SHOW INDEX FROM '.$table);
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($wanted as $name) {
            $out[$name] = $name;
        }
        foreach ($rows as $row) {
            $n = (string) ($row->Key_name ?? '');
            if (in_array($n, $wanted, true)) {
                $out[$n] = $n;
            }
        }

        return $out;
    }

    private function indexExists(string $table, string $name): bool
    {
        try {
            $rows = DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$name]);
        } catch (\Throwable $e) {
            return false;
        }

        return count($rows) > 0;
    }

    /**
     * @param Blueprint $table
     * @param string $indexName
     * @param array<int, string> $columns
     * @param array<string, string> $existingNames
     * @param bool $unique
     */
    private function safeAddIndex(Blueprint $table, string $indexName, array $columns, array $existingNames, bool $unique = false): void
    {
        if (isset($existingNames[$indexName]) && $existingNames[$indexName] !== '' && $this->indexExists($table->getTable(), $existingNames[$indexName])) {
            return;
        }

        if ($unique) {
            $table->unique($columns, $indexName);
        } else {
            $table->index($columns, $indexName);
        }
    }
};
