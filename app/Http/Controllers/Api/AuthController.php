<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->success([
            'user' => $user,
        ], 'Cont creat cu succes.', 201);
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->validated())) {
            throw ValidationException::withMessages([
                'email' => ['Datele de autentificare sunt incorecte.'],
            ]);
        }

        $request->session()->regenerate();

        return $this->success([
            'user' => Auth::user(),
        ], 'Autentificare reușită.');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Delogat cu succes.');
    }
}
