<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createRoleUser([
            'password' => bcrypt('12345678'),
        ]);
    }

    public function test_メールアドレス未入力(){
        $data = [
            'email' => $this->user->email,
            'password' => '12345678',
        ];
        $data['email'] = '';

        $this->from('/login')
        ->post('/login',$data)
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワード未入力(){
        $data = [
            'email' => $this->user->email,
            'password' => '12345678',
        ];
        $data['password'] = '';

        $this->from('/login')
        ->post('/login',$data)
        ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_メールアドレス不一致(){
        $data = [
            'email' => $this->user->email,
            'password' => '12345678',
        ];
        $data['email'] = 'test@exam.com';

        $response = $this->from('/login')
            ->post('/login',$data);
        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );

    }

    public function test_パスワード不一致(){
        $data = [
            'email' => $this->user->email,
            'password' => '12345678',
        ];
        $data['password'] = '12345689';

        $response = $this->from('/login')
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
            'email' => $this->user->email,
            'password' => '12345678',
        ];

        $this->post('/login', $data)
            ->assertRedirect('/attendance');

        $this->assertAuthenticatedAs($this->user);
    }
}
