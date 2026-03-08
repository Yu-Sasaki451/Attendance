<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function base():array
    {
        return[
            'name' => 'テストユーザー',
            'email' => 'user_test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];
    }

    public function test_名前未入力(){

        $data = $this->base();
        $data['name'] = '';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    public function test_メールアドレス未入力(){

        $data = $this->base();
        $data['email'] = '';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワード未入力(){

        $data = $this->base();
        $data['password'] = '';
        $data['password_confirmation'] = '';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_パスワード8文字未満(){

        $data = $this->base();
        $data['password'] = '1234567';
        $data['password_confirmation'] = '1234567';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_パスワード不一致(){

        $data = $this->base();
        $data['password'] = '1234567';
        $data['password_confirmation'] = '7654321';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    public function test_登録(){

        $data = $this->base();
        $this->post('/register',$data)
        ->assertRedirect('/attendance');
    }
}
