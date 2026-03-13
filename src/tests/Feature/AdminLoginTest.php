<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createRoleAdmin([
            'password' => bcrypt('12345678'),
        ]);
    }

    public function test_メールアドレス未入力(){
        $data = [
            'email' => $this->admin->email,
            'password' => '12345678',
            'login_type' => 'admin',
        ];
        $data['email'] = '';

        $this->from('/admin/login')
        ->post('/login',$data)
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワード未入力(){
        $data = [
            'email' => $this->admin->email,
            'password' => '12345678',
            'login_type' => 'admin',
        ];
        $data['password'] = '';

        $this->from('/admin/login')
        ->post('/login',$data)
        ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_メールアドレス不一致(){
        $data = [
            'email' => $this->admin->email,
            'password' => '12345678',
            'login_type' => 'admin',
        ];
        $data['email'] = 'test@exam.com';

        $response = $this->from('/admin/login')
            ->post('/login',$data);
        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );

    }

    public function test_パスワード不一致(){
        $data = [
            'email' => $this->admin->email,
            'password' => '12345678',
            'login_type' => 'admin',
        ];
        $data['password'] = '12345689';

        $response = $this->from('/admin/login')
            ->post('/login',$data);
        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );

    }

    public function test_ログイン成功()
    {
        $data = [
            'email' => $this->admin->email,
            'password' => '12345678',
            'login_type' => 'admin',
        ];

        $this->post('/login', $data)
            ->assertRedirect('/admin/attendance/list');

        $this->assertAuthenticatedAs($this->admin);
    }
}
