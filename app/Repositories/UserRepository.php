<?php 

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function countAll()
    {
        return User::count();
    }
}