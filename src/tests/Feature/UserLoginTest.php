<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    //DBを毎回フレッシュにする
    use RefreshDatabase;

    //setUpで使うための箱を用意する
    private $user;
    private $data;

    //各テスト前にsetUpが実行される
    protected function setUp(): void
    {
        //テストする環境を初期化
        parent::setUp();

        $password = '12345678';

        //ユーザーを1人作る、パスワードを設定する
        $this->user = $this->createRoleUser([
            'password' => bcrypt($password),
        ]);

        //アドレスとパスワードを設定する
        $this->data = [
            'email' => $this->user->email,
            'password' => $password,
        ];
    }

    public function test_メールアドレス未入力(){
        $this->data['email'] = '';

        $this->from('/login')
        ->post('/login',$this->data)
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワード未入力(){
        $this->data['password'] = '';

        $this->from('/login')
        ->post('/login',$this->data)
        ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_メールアドレス形式不正(){
        $this->data['email'] = 'test1234';

        $this->from('/login')
        ->post('/login',$this->data)
        ->assertSessionHasErrors(['email' => 'アドレス形式で入力してください']);
    }

    public function test_メールアドレス不一致(){
        $this->data['email'] = 'tes@exam.com';

        $response = $this->from('/login')
            ->post('/login',$this->data)
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

    }

    public function test_パスワード不一致(){
        $this->data['password'] = '23456895';

        $response = $this->from('/login')
            ->post('/login',$this->data)
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

    }

    public function test_ログイン成功()
    {    $this->post('/login', $this->data)
            ->assertRedirect('/attendance');

        $this->assertAuthenticatedAs($this->user);
    }
}
