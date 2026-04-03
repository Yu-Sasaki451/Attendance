<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $data;

    protected function setUp(): void
    {
        parent::setUp();

        $password = '12345678';

        $this->admin = $this->createRoleAdmin([
            'password' => bcrypt($password),
        ]);

        $this->data =[
            'email' => $this->admin->email,
            'password' => $password,
            'login_type' => 'admin',
        ];
    }

    public function test_メールアドレス未入力(){
        $this->data['email'] = '';

        $this->from('/admin/login')
        ->post('/login',$this->data)
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワード未入力(){
        $this->data['password'] = '';

        $this->from('/admin/login')
        ->post('/login',$this->data)
        ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_メールアドレス不一致(){
        $this->data['email'] = 'test@exam.com';

        $response = $this->from('/admin/login')
            ->post('/login',$this->data);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

    }

    public function test_パスワード不一致(){
        $this->data['password'] = '12345689';

        $response = $this->from('/admin/login')
            ->post('/login',$this->data);
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

    }

    public function test_ログイン成功()
    {
        $this->post('/login', $this->data)
            ->assertRedirect('/admin/attendance/list');

        $this->assertAuthenticatedAs($this->admin);
    }
}
