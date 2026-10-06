<?php

namespace App\Events;

use App\Models\Poll;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteCast implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Poll $poll)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('poll.' . $this->poll->slug)];
    }

    public function broadcastAs(): string
    {
        return 'vote.cast';
    }

    public function broadcastWith(): array
    {
        $options = $this->poll->options()->get(['id', 'option_text', 'votes_count']);

        return [
            'slug'        => $this->poll->slug,
            'total_votes' => $options->sum('votes_count'),
            'options'     => $options->map(fn ($option) => [
                'id'          => $option->id,
                'text'        => $option->option_text,
                'votes_count' => $option->votes_count,
            ]),
        ];
    }
}