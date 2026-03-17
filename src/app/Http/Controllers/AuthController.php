<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function adminLogin()
    {
        return view('auth.admin_login');
    }
}
