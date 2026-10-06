<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Services\PollService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PollController extends Controller
{
    use ApiResponse;

    public function __construct(protected PollService $pollService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Poll::class);

        return $this->success($request->user()->polls()->with('options')->get());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Poll::class);

        $validated = $request->validate([
            'question'   => 'required|string|max:255',
            'options'    => 'required|array|min:2',
            'options.*'  => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $poll = $this->pollService->createPoll($request->user(), $validated);

        return $this->created($poll);
    }
}