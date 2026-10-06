<?php

namespace App\Http\Controllers;

use App\Contracts\VoterResolver;
use App\Exceptions\AlreadyVotedException;
use App\Exceptions\PollClosedException;
use App\Models\Poll;
use App\Services\VoteService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PublicPollController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected VoteService $voteService,
        protected VoterResolver $voterResolver,
    ) {
    }

    public function show(Poll $poll)
    {
        return $this->success([
            'question'   => $poll->question,
            'slug'       => $poll->slug,
            'expires_at' => $poll->expires_at,
            'is_expired' => $poll->isExpired(),
            'options'    => $poll->options->map(fn ($option) => [
                'id'          => $option->id,
                'text'        => $option->option_text,
                'votes_count' => $option->votes_count,
            ]),
        ]);
    }

    public function vote(Request $request, Poll $poll)
    {
        $validated = $request->validate([
            'poll_option_id' => 'required|integer',
        ]);

        try {
            $this->voteService->castVote(
                $poll,
                $validated['poll_option_id'],
                $this->voterResolver->resolve($request),
            );
        } catch (PollClosedException $e) {
            return $this->forbidden($e->getMessage());
        } catch (AlreadyVotedException $e) {
            return $this->error($e->getMessage(), 409);
        }

        return $this->created(null, 'Vote recorded');
    }
}