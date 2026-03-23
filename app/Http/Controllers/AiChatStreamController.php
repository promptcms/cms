<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CmsAgent;
use App\Models\AiSession;
use App\Models\MediaContainer;
use App\Services\CssBuildService;
use Illuminate\Http\Request;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatStreamController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        set_time_limit(300);

        $request->validate([
            'prompt' => 'required_without:attachments|nullable|string|max:10000',
            'session_id' => 'nullable|string',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg,bmp,ico',
        ]);

        $prompt = $request->input('prompt', '');
        $sessionId = $request->input('session_id');

        // Handle file uploads — store in media library
        $attachmentData = [];

        if ($request->hasFile('attachments')) {
            $container = MediaContainer::firstOrCreate(['name' => 'global']);

            foreach ($request->file('attachments') as $file) {
                $media = $container->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->toMediaCollection('default');

                $attachmentData[] = [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'mime_type' => $media->mime_type,
                ];
            }
        }

        // Build prompt with file references
        $fullPrompt = $prompt;

        if (! empty($attachmentData)) {
            $fileDescs = collect($attachmentData)->map(function (array $att) {
                return "[Hochgeladene Datei: {$att['name']} (URL: {$att['url']})]";
            })->implode("\n");
            $fullPrompt = $fileDescs."\n\n".$prompt;
        }

        $session = $sessionId
            ? AiSession::find($sessionId)
            : AiSession::create(['title' => str($prompt)->limit(60)->toString(), 'messages' => []]);

        $session->addMessage([
            'role' => 'user',
            'content' => $prompt,
            'attachments' => $attachmentData,
        ]);

        $agent = new CmsAgent($session);

        return response()->stream(function () use ($agent, $fullPrompt, $session, $attachmentData) {
            // Disable all output buffering for SSE
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
            }

            $this->sendEvent('session', ['id' => $session->id, 'attachments' => $attachmentData]);

            try {
                $streamResponse = $agent->stream($fullPrompt);
                $fullText = '';
                $toolCalls = [];

                foreach ($streamResponse as $event) {
                    if ($event instanceof TextDelta) {
                        $fullText .= $event->delta;
                        $this->sendEvent('text', ['delta' => $event->delta]);
                    } elseif ($event instanceof ToolCall) {
                        $toolCalls[] = $event->toolCall->name;
                        $this->sendEvent('tool_call', [
                            'name' => $event->toolCall->name,
                        ]);
                    } elseif ($event instanceof ToolResult) {
                        $this->sendEvent('tool_result', [
                            'name' => $event->toolResult->toolName ?? '',
                        ]);
                    }

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                $session->addMessage(['role' => 'assistant', 'content' => $fullText]);

                if (count($toolCalls) > 0) {
                    CssBuildService::compileSync();
                }

                $this->sendEvent('done', ['tool_calls' => $toolCalls]);
            } catch (\Throwable $e) {
                $errorMessage = 'Fehler: '.$e->getMessage();
                $session->addMessage(['role' => 'assistant', 'content' => $errorMessage]);
                $this->sendEvent('error', ['message' => $errorMessage]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendEvent(string $type, array $data): void
    {
        echo 'data: '.json_encode(['type' => $type, ...$data], JSON_UNESCAPED_UNICODE)."\n\n";
    }
}
