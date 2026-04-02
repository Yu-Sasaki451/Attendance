<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
     //DBを毎回フレッシュにする
    use RefreshDatabase;

    //setUpで使うための箱を用意する
    private $data;

    protected function setUp(): void
    {
        //テスト環境を初期化
        parent::setUp();

        //登録に使用する値を作る
        $this->data = [
            'name' => 'テストユーザー',
            'email' => 'user_test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];
    }

    public function test_名前未入力(){

        $data = $this->data;
        $data['name'] = '';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    public function test_メールアドレス未入力(){

        $data = $this->data;
        $data['email'] = '';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_名前が20文字超過(){

        $data = $this->data;
        $data['name'] = '123456789012345678901';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['name' => '20文字以内で入力してください']);
    }

    public function test_メールアドレス形式不正(){

        $data = $this->data;
        $data['email'] = 'test123';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['email' => 'アドレス形式で入力してください']);
    }

    public function test_パスワード未入力(){

        $data = $this->data;
        $data['password'] = '';
        $data['password_confirmation'] = '';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_パスワード8文字未満(){

        $data = $this->data;
        $data['password'] = '1234567';
        $data['password_confirmation'] = '1234567';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_パスワード不一致(){

        $data = $this->data;
        $data['password'] = '1234567';
        $data['password_confirmation'] = '7654321';

        $this->from('/register')
        ->post('/register',$data)
        ->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    public function test_登録(){

        $data = $this->data;
        $this->post('/register',$data)
        ->assertRedirect('/attendance');
    }
}
