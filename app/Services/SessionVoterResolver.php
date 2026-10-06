<?php

namespace App\Services;

use App\Contracts\VoterResolver;
use Illuminate\Http\Request;

class SessionVoterResolver implements VoterResolver
{
    public function resolve(Request $request): array
    {
        return [
            'user_id'          => $request->user('sanctum')?->id,
            'voter_identifier' => hash('sha256', $request->session()->getId()),
            'ip_address'       => $request->ip(),
        ];
    }
}