<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $idWilaya = 16;
        $idCommune = (int) (DB::table('commune')->where('ID_WILAYA', $idWilaya)->value('ID_COMMUNE') ?? 1);

        $this->upsertFournisseur(
            nomFrs: 'Bona GSM',
            email: 'admin@bonagsm.com',
            telephone: '0550000000',
            idWilaya: $idWilaya,
            idCommune: $idCommune,
            password: 'Alger2324',
        );
    }

    private function upsertFournisseur(
        string $nomFrs,
        string $email,
        string $telephone,
        int $idWilaya,
        int $idCommune,
        string $password = 'Password@123',
    ): void {
        $frs = Fournisseur::firstOrNew(['email' => $email]);
        $frs->nom_frs = $nomFrs;
        $frs->password = Hash::make($password);
        $frs->telephone = $telephone;
        $frs->adresse = 'Alger';
        $frs->id_wilaya = $idWilaya;
        $frs->id_commune = $idCommune;
        $frs->actif = 1;

        if (! is_string($frs->token) || trim($frs->token) === '') {
            $frs->token = (string) Str::uuid();
        }

        $frs->save();
    }
}
