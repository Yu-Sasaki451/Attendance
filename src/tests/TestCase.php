<?php

namespace Tests;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function createRoleUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
        ], $overrides));
    }

    protected function createAttendanceFor(User $user, array $overrides = []): Attendance
    {
        return Attendance::create(array_merge([
            'user_id' => $user->id,
            'date' => today(),
            'in_at' => null,
            'out_at' => null,
        ], $overrides));
    }

    protected function jpWeekday(int $dayOfWeek): string
    {
        return ['日', '月', '火', '水', '木', '金', '土'][$dayOfWeek];
    }
}
