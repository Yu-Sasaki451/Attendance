<?php

namespace Tests;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /* -----ユーザーを1人作る----- */
    protected function createRoleUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
        ], $overrides));
    }

    /* -----管理者を1人作る----- */
    protected function createRoleAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
        ], $overrides));
    }

    /* -----勤怠情報の土台----- */
    protected function createAttendanceFor(User $user, array $overrides = []): Attendance
    {
        return Attendance::create(array_merge([
            'user_id' => $user->id,
            'date' => today(),
            'in_at' => null,
            'out_at' => null,
            'note' => null,
        ], $overrides));
    }

    /* -----休憩情報の土台-----　*/
    protected function createBreakTimeFor(Attendance $attendance,array $overrides = []): BreakTime
    {
        return BreakTime::create(array_merge([
            'attendance_id' => $attendance->id,
            'in_at' => null,
            'out_at' => null,
        ], $overrides));
    }

    protected function jpWeekday(int $dayOfWeek): string
    {
        return ['日', '月', '火', '水', '木', '金', '土'][$dayOfWeek];
    }

    /* -----ユーザー3人と管理者を作る----- */
    protected function createAdminAndUsers(): array
{
    $admin = $this->createRoleAdmin();

    $user1 = $this->createRoleUser([
        'name' => 'テストユーザー1',
        'email' => 'test1@example.com',
    ]);

    $user2 = $this->createRoleUser([
        'name' => 'テストユーザー2',
        'email' => 'test2@example.com',
    ]);

    $user3 = $this->createRoleUser([
        'name' => 'テストユーザー3',
        'email' => 'test3@example.com',
    ]);

    return [
        'admin' => $admin,
        'users' => [$user1, $user2, $user3],
    ];
}

}
