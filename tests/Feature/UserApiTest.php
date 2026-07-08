<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'firstname' => 'Jean',
        'lastname' => 'Dupont',
        'email' => 'jean.dupont@example.com',
        'password' => 'Password1',
        'birthdate' => '2000-01-01',
    ];

    public function test_creation_user_valide_retourne_201(): void
    {
        $response = $this->postJson('/api/users', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonFragment(['email' => 'jean.dupont@example.com'])
            ->assertJsonFragment(['firstname' => 'Jean']);

        $this->assertDatabaseHas('users', ['email' => 'jean.dupont@example.com']);
    }

    public function test_creation_user_email_invalide_retourne_422(): void
    {
        $payload = array_merge($this->validPayload, ['email' => 'pas-un-email']);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['firstname' => 'Jean']);
    }

    public function test_creation_user_sans_firstname_retourne_422(): void
    {
        $payload = array_merge($this->validPayload, ['firstname' => '']);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(422);
    }

    public function test_creation_user_password_trop_court_retourne_422(): void
    {
        $payload = array_merge($this->validPayload, ['password' => 'Abc1']);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(422);
    }

    public function test_creation_user_password_sans_minuscule_retourne_422(): void
    {
        $payload = array_merge($this->validPayload, ['password' => 'PASSWORD1']);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(422);
    }

    public function test_creation_user_password_sans_chiffre_retourne_422(): void
    {
        $payload = array_merge($this->validPayload, ['password' => 'PasswordAbc']);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(422);
    }

    public function test_creation_user_age_inferieur_a_13_ans_retourne_422(): void
    {
        $payload = array_merge($this->validPayload, [
            'birthdate' => now()->subYears(12)->format('Y-m-d'),
        ]);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(422);
    }

    public function test_creation_user_age_exactement_13_ans_retourne_201(): void
    {
        $payload = array_merge($this->validPayload, [
            'birthdate' => now()->subYears(13)->format('Y-m-d'),
        ]);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(201);
    }

    public function test_get_user_existant_retourne_200(): void
    {
        $user = User::create($this->validPayload);

        $response = $this->getJson('/api/users/'.$user->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => 'jean.dupont@example.com']);
    }

    public function test_get_user_inexistant_retourne_404(): void
    {
        $response = $this->getJson('/api/users/9999');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Utilisateur introuvable.']);
    }

    public function test_get_user_ne_retourne_pas_le_password(): void
    {
        $user = User::create($this->validPayload);

        $response = $this->getJson('/api/users/'.$user->id);

        $response->assertStatus(200)
            ->assertJsonMissingPath('password');
    }

    public function test_update_user_valide_retourne_200(): void
    {
        $user = User::create($this->validPayload);

        $response = $this->putJson('/api/users/'.$user->id, [
            'firstname' => 'Pierre',
            'lastname' => 'Martin',
            'email' => 'pierre.martin@example.com',
            'password' => 'NewPass1',
            'birthdate' => '1995-06-15',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['firstname' => 'Pierre']);

        $this->assertDatabaseHas('users', ['firstname' => 'Pierre']);
    }

    public function test_update_user_inexistant_retourne_404(): void
    {
        $response = $this->putJson('/api/users/9999', $this->validPayload);

        $response->assertStatus(404);
    }

    public function test_update_user_donnees_invalides_retourne_422(): void
    {
        $user = User::create($this->validPayload);

        $response = $this->putJson('/api/users/'.$user->id, [
            'firstname' => 'Pierre',
            'lastname' => 'Martin',
            'email' => 'invalide',
            'password' => 'NewPass1',
            'birthdate' => '1995-06-15',
        ]);

        $response->assertStatus(422);
    }

    public function test_delete_user_existant_retourne_204(): void
    {
        $user = User::create($this->validPayload);

        $response = $this->deleteJson('/api/users/'.$user->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_delete_user_inexistant_retourne_404(): void
    {
        $response = $this->deleteJson('/api/users/9999');

        $response->assertStatus(404);
    }
}
