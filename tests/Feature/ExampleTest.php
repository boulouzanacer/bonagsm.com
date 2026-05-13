<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        DB::table('wilaya')->insert([
            'ID_WILAYA' => 1,
            'WILAYA' => 'Test',
            'WILAYA2' => 'Test',
        ]);
        DB::table('commune')->insert([
            'ID_COMMUNE' => 1,
            'COMMUNE' => 'Test',
            'ID_WILAYA' => 1,
        ]);
        DB::table('frs')->insert([
            'id' => 1,
            'nom_frs' => 'Fournisseur Test',
            'email' => 'frs@test.tld',
            'password' => bcrypt('Password@123'),
            'telephone' => '0550000000',
            'adresse' => 'Test',
            'id_wilaya' => 1,
            'id_commune' => 1,
            'token' => '00000000-0000-0000-0000-000000000000',
            'actif' => 1,
            'is_visible' => 1,
            'show_prices_to_guests' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
