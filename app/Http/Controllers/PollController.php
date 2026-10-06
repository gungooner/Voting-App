<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PollController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Poll::class);

        return $request->user()->polls()->with('options')->get();
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

        do {
            $slug = Str::random(8);
        } while (Poll::where('slug', $slug)->exists());

        $poll = $request->user()->polls()->create([
            'question'   => $validated['question'],
            'slug'       => $slug,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        foreach ($validated['options'] as $optionText) {
            $poll->options()->create(['option_text' => $optionText]);
        }

        return response()->json($poll->load('options'), 201);
    }
}