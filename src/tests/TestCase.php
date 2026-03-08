<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function makeUser(array $attributes = []): User
    {
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'user_test@example.com',
            'password' => bcrypt('12345678'),
            'role' => 'user',
        ];

        $user = User::factory()->create(array_merge($userData, $attributes));

        return $user;
    }

    protected function makeAdmin(array $attributes = []): User
    {
        $adminData = [
            'email' => 'admin_test@example.com',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
        ];

        $admin = User::factory()->create(array_merge($adminData,$attributes));

        return $admin;
    }
}
