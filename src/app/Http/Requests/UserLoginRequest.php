<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\LoginRateLimiter;

class UserLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules():array
    {
        return [
            'email' => 'required',
            'password' =>'required',
        ];
    }

    public function messages():array{
        return [
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ];
    }

    public function withValidator($validator){

        $validator->after(function($validator){
        $email = $this->input('email');
        $password = $this->input('password');
        $loginType = $this->input('login_type');

        if(!$email || !$password) {return;}

        $query = User::where('email', $email);

            if ($loginType === 'admin') {
                $query->where('role', 'admin');
            } else {
                $query->where('role', 'user');
            }

            $user = $query->first();

            if (! $user || ! Hash::check($password, $user->password)) {
                app(LoginRateLimiter::class)->increment($this);
                $validator->errors()->add('email', 'ログイン情報が登録されていません。');
            }
    });
}}