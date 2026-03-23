<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class AiSession extends Model
{
    use HasUlids;

    protected $fillable = [
        'title',
        'messages',
        'context_summary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'messages' => 'array',
        ];
    }

    /**
     * Add a message to the session.
     *
     * @param  array{role: string, content: string}  $message
     */
    public function addMessage(array $message): void
    {
        $messages = $this->messages ?? [];
        $messages[] = $message;
        $this->update(['messages' => $messages]);
    }
}
