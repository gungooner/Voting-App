<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface VoterResolver
{
    /**
     * @return array{user_id: int|null, voter_identifier: string, ip_address: string|null}
     */
    public function resolve(Request $request): array;
}