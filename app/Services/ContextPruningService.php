<?php

namespace App\Services;

use App\Models\AiSession;

class ContextPruningService
{
    /**
     * Max tokens to allow in conversation history before pruning.
     * ~20k tokens ≈ 80k chars leaves plenty of room for instructions + tools.
     */
    private const MAX_HISTORY_TOKENS = 20000;

    /**
     * Number of recent messages to always keep unmodified.
     */
    private const KEEP_RECENT_MESSAGES = 6;

    /**
     * Rough chars-per-token estimate.
     */
    private const CHARS_PER_TOKEN = 4;

    /**
     * Prune the session's messages if they exceed the token limit.
     * Older messages are summarized into context_summary.
     * Returns the pruned messages array for the AI.
     *
     * @return array<array{role: string, content: string}>
     */
    public function prune(AiSession $session): array
    {
        $messages = $session->messages ?? [];

        if (count($messages) <= self::KEEP_RECENT_MESSAGES) {
            return $this->withContextSummary($session, $messages);
        }

        $totalTokens = $this->estimateTokens($messages);

        if ($totalTokens <= self::MAX_HISTORY_TOKENS) {
            return $this->withContextSummary($session, $messages);
        }

        // Split: older messages to summarize, recent to keep
        $recentCount = min(self::KEEP_RECENT_MESSAGES, count($messages));
        $olderMessages = array_slice($messages, 0, count($messages) - $recentCount);
        $recentMessages = array_slice($messages, -$recentCount);

        // Build summary from older messages
        $summary = $this->summarizeMessages($olderMessages, $session->context_summary);

        // Persist the summary and trimmed messages
        $session->update([
            'context_summary' => $summary,
            'messages' => $recentMessages,
        ]);

        return $this->withContextSummary($session->fresh(), $recentMessages);
    }

    /**
     * Prepend context summary as a system-like message if it exists.
     *
     * @param  array<array{role: string, content: string}>  $messages
     * @return array<array{role: string, content: string}>
     */
    private function withContextSummary(AiSession $session, array $messages): array
    {
        if (empty($session->context_summary)) {
            return $messages;
        }

        // Inject summary as the first user message so the AI has context
        $summaryMessage = [
            'role' => 'user',
            'content' => "[Zusammenfassung bisheriger Konversation]\n".$session->context_summary."\n[Ende Zusammenfassung — die folgenden Nachrichten sind aktuell]",
        ];

        return [$summaryMessage, ...$messages];
    }

    /**
     * Create a text summary of the older messages.
     */
    private function summarizeMessages(array $messages, ?string $existingSummary = null): string
    {
        $parts = [];

        if ($existingSummary) {
            $parts[] = $existingSummary;
        }

        foreach ($messages as $msg) {
            $role = $msg['role'] === 'user' ? 'Benutzer' : 'Assistent';
            $content = $msg['content'] ?? '';

            // Truncate very long messages (e.g. full page HTML)
            if (strlen($content) > 500) {
                $content = mb_substr($content, 0, 400).'... [gekürzt]';
            }

            $parts[] = "- {$role}: {$content}";
        }

        // Keep summary itself under ~2000 chars
        $summary = implode("\n", $parts);

        if (strlen($summary) > 2000) {
            $summary = mb_substr($summary, 0, 1900).'... [weitere Einträge gekürzt]';
        }

        return $summary;
    }

    /**
     * Rough token estimate for a set of messages.
     */
    private function estimateTokens(array $messages): int
    {
        $chars = 0;

        foreach ($messages as $msg) {
            $chars += strlen($msg['content'] ?? '');
        }

        return (int) ceil($chars / self::CHARS_PER_TOKEN);
    }
}
