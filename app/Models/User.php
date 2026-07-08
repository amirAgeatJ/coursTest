<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['firstname', 'lastname', 'email', 'password', 'birthdate'])]
#[Hidden(['password'])]
class User extends Model
{
    public function isValid(): bool
    {
        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (empty($this->firstname) || empty($this->lastname)) {
            return false;
        }

        $password = $this->password;
        if (
            strlen($password) < 8 ||
            strlen($password) > 40 ||
            ! preg_match('/[a-z]/', $password) ||
            ! preg_match('/[A-Z]/', $password) ||
            ! preg_match('/[0-9]/', $password)
        ) {
            return false;
        }

        $birthdate = new \DateTime($this->birthdate);
        $today = new \DateTime;
        $age = $today->diff($birthdate)->y;

        if ($age < 13) {
            return false;
        }

        return true;
    }
}
