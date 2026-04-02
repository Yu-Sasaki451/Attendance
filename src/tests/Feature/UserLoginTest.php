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

        //ユーザーを1人作る、パスワードを設定する
        $this->user = $this->createRoleUser([
            'password' => bcrypt('12345678'),
        ]);

        //アドレスとパスワードを設定する
        $this->data = [
            'email' => $this->user->email,
            'password' => '12345678',
        ];
    }

    public function test_メールアドレス未入力(){
        $data = $this->data;
        $data['email'] = '';

        $this->from('/login')
        ->post('/login',$data)
        ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_パスワード未入力(){
        $data = $this->data;
        $data['password'] = '';

        $this->from('/login')
        ->post('/login',$data)
        ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_メールアドレス形式不正(){
        $data = $this->data;
        $data['email'] = 'test1234';

        $this->from('/login')
        ->post('/login',$data)
        ->assertSessionHasErrors(['email' => 'アドレス形式で入力してください']);
    }

    public function test_メールアドレス不一致(){
        $data = $this->data;
        $data['email'] = 'tes@exam.com';

        $response = $this->from('/login')
            ->post('/login',$data)
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

    }

    public function test_パスワード不一致(){
        $data = $this->data;
        $data['password'] = '23456895';

        $response = $this->from('/login')
            ->post('/login',$data)
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません。']);

    }

    public function test_ログイン成功()
    {
        $data = $this->data;
        $this->post('/login', $data)
            ->assertRedirect('/attendance');

        $this->assertAuthenticatedAs($this->user);
    }
}
