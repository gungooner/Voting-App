<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService)
    {
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        return $this->created($this->authService->register($validated), 'Registered successfully');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->attemptLogin($validated['email'], $validated['password']);

        if ($result === null) {
            return $this->error('Invalid credentials', 401);
        }

        return $this->success($result, 'Logged in successfully');
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->noContent();
    }
}