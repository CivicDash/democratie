<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\TopicBallot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TopicBallot>
 */
class TopicBallotFactory extends Factory
{
    protected $model = TopicBallot::class;

    public function definition(): array
    {
        $topic = Topic::factory()->withBallot()->create();
        $vote = $this->generateVote($topic);
        $nonce = bin2hex(random_bytes(16));
        
        return [
            'topic_id' => $topic->id,
            'encrypted_vote' => TopicBallot::encryptVote($vote),
            'vote_hash' => TopicBallot::hashVote($topic->id, $vote, $nonce),
            'cast_at' => fake()->dateTimeBetween($topic->voting_opens_at, $topic->voting_deadline_at ?? 'now'),
        ];
    }

    protected function generateVote(Topic $topic): array
    {
        if ($topic->ballot_type === 'yes_no') {
            return ['choice' => fake()->randomElement(['yes', 'no'])];
        }
        
        if ($topic->ballot_type === 'multiple_choice' && $topic->ballot_options) {
            $options = $topic->ballot_options['options'] ?? [];
            $option = fake()->randomElement($options);
            return ['choice' => $option['id']];
        }
        
        return ['choice' => 'yes']; // Fallback
    }

    public function yesVote(): static
    {
        return $this->state(function (array $attributes) {
            $vote = ['choice' => 'yes'];
            $nonce = bin2hex(random_bytes(16));
            
            return [
                'encrypted_vote' => TopicBallot::encryptVote($vote),
                'vote_hash' => TopicBallot::hashVote($attributes['topic_id'], $vote, $nonce),
            ];
        });
    }

    public function noVote(): static
    {
        return $this->state(function (array $attributes) {
            $vote = ['choice' => 'no'];
            $nonce = bin2hex(random_bytes(16));
            
            return [
                'encrypted_vote' => TopicBallot::encryptVote($vote),
                'vote_hash' => TopicBallot::hashVote($attributes['topic_id'], $vote, $nonce),
            ];
        });
    }
}

