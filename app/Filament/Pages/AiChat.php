<?php

namespace App\Filament\Pages;

use App\Models\AiSession;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AiChat extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'KI-Chat';

    protected static ?string $title = 'KI-Chat';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.pages.ai-chat';

    public function getHeading(): string
    {
        return '';
    }

    public ?string $sessionId = null;

    public array $chatMessages = [];

    /**
     * @var Collection<int, AiSession>
     */
    public Collection $sessions;

    public function mount(): void
    {
        $this->loadSessions();

        if ($this->sessionId) {
            $this->loadSession($this->sessionId);
        } elseif ($this->sessions->isNotEmpty()) {
            $this->loadSession($this->sessions->first()->id);
        }
    }

    public function loadSessions(): void
    {
        $this->sessions = AiSession::query()
            ->latest()
            ->limit(50)
            ->get();
    }

    public function loadSession(string $sessionId): void
    {
        $session = AiSession::find($sessionId);

        if (! $session) {
            return;
        }

        $this->sessionId = $session->id;
        $this->chatMessages = $session->messages ?? [];
        $this->dispatch('session-loaded');
    }

    public function newSession(): void
    {
        $this->sessionId = null;
        $this->chatMessages = [];
        $this->dispatch('session-loaded');
    }

    /**
     * Called from JS after streaming completes to sync state.
     */
    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function addUserMessage(string $sessionId, string $userMessage, array $attachments = []): void
    {
        $this->sessionId = $sessionId;
        $this->chatMessages[] = [
            'role' => 'user',
            'content' => $userMessage,
            'attachments' => $attachments,
        ];
        $this->loadSessions();
    }

    public function streamCompleted(string $sessionId, string $assistantMessage): void
    {
        $this->sessionId = $sessionId;
        $this->chatMessages[] = ['role' => 'assistant', 'content' => $assistantMessage];
        $this->loadSessions();
        $this->dispatch('stream-finished');
    }
}
