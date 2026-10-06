<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        return $this->withToken($user);
    }

    public function attemptLogin(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $this->withToken($user);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    protected function withToken(User $user): array
    {
        return [
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}