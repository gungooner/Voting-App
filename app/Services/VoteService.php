<?php

namespace App\Services;

use App\Exceptions\AlreadyVotedException;
use App\Exceptions\PollClosedException;
use App\Models\Poll;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use App\Events\VoteCast;

class VoteService
{
        public function castVote(Poll $poll, int $optionId, array $voter): Vote
    {
        if ($poll->isExpired()) {
            throw new PollClosedException('This poll has closed.');
        }

        $option = $poll->options()->findOrFail($optionId);

        try {
            $vote = DB::transaction(function () use ($poll, $option, $voter) {
                $vote = Vote::create([
                    'poll_id'          => $poll->id,
                    'poll_option_id'   => $option->id,
                    'user_id'          => $voter['user_id'],
                    'voter_identifier' => $voter['voter_identifier'],
                    'ip_address'       => $voter['ip_address'],
                ]);

                $option->increment('votes_count');

                return $vote;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw new AlreadyVotedException('You have already voted in this poll.');
        }

        $this->broadcastResults($poll);

        return $vote;
    }

    protected function broadcastResults(Poll $poll): void
    {
        try {
            VoteCast::dispatch($poll);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}