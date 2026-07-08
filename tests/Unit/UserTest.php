<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_valide(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertTrue($user->isValid());
    }

    public function test_email_invalide(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'invalide',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_firstname_manquant(): void
    {
        $user = new User([
            'firstname' => '',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_lastname_manquant(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => '',
            'email' => 'jean.dupont@email.com',
            'password' => 'Password1',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_password_trop_court(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'Abc1',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_password_trop_long(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'Abcdefgh1'.str_repeat('x', 35),
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_password_sans_majuscule(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'password1',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_password_sans_minuscule(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'PASSWORD1',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_password_sans_chiffre(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'PasswordAbc',
            'birthdate' => '2000-01-01',
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_age_moins_de_13_ans(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'Password1',
            'birthdate' => now()->subYears(12)->format('Y-m-d'),
        ]);
        $this->assertFalse($user->isValid());
    }

    public function test_age_exactement_13_ans(): void
    {
        $user = new User([
            'firstname' => 'Jean',
            'lastname' => 'Dupont',
            'email' => 'jean.dupont@email.com',
            'password' => 'Password1',
            'birthdate' => now()->subYears(13)->format('Y-m-d'),
        ]);
        $this->assertTrue($user->isValid());
    }
}
