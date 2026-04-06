<x-filament-panels::page :full-height="true">
    <style>
        /* Kill all parent scrolling */
        .fi-body:has(.ai-chat-root) { overflow: hidden; height: 100vh; }
        .fi-body:has(.ai-chat-root) .fi-main { overflow: hidden; }

        /* Fixed height container — accounts for Filament topbar (~4rem) and page padding */
        .ai-chat-root { height: calc(100vh - 5rem); display: flex; flex-direction: column; overflow: hidden; }

        /* Equal spacing top and bottom */
        .fi-body:has(.ai-chat-root) .fi-page-content { padding-bottom: 1rem !important; }

        /* Chat container: sidebar + main — mb for bottom spacing */
        .ai-chat-container { flex: 1; min-height: 0; display: flex; gap: 0.5rem; overflow: hidden; margin-bottom: 1rem; }

        /* Main chat column */
        .ai-chat-column { flex: 1; min-height: 0; min-width: 0; display: flex; flex-direction: column; }

        /* ONLY messages scroll */
        .chat-messages { flex: 1; min-height: 0; overflow-y: auto; }

        /* Input pinned at bottom */
        .chat-input { flex-shrink: 0; }
    </style>

    <div class="ai-chat-root" x-data="aiChat()" x-init="scrollToBottom()">
    <div class="ai-chat-container relative">
        {{-- Sidebar: Sessions --}}
        <div class="hidden w-56 flex-shrink-0 flex-col overflow-y-auto rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:flex">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sessions</h3>
                <button
                    wire:click="newSession"
                    class="rounded-lg bg-primary-600 p-1.5 text-white shadow-sm hover:bg-primary-500 transition-colors"
                    title="Neue Session"
                >
                    <x-heroicon-m-plus class="h-4 w-4" />
                </button>
            </div>

            <div class="flex-1 space-y-1 overflow-y-auto" x-ref="sessionList">
                @forelse($sessions as $session)
                    <button
                        wire:click="loadSession('{{ $session->id }}')"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm transition-colors
                            {{ $sessionId === $session->id
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                    >
                        <div class="truncate font-medium">{{ $session->title ?? 'Unbenannt' }}</div>
                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $session->created_at->diffForHumans() }}
                        </div>
                    </button>
                @empty
                    <p class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">Noch keine Sessions.</p>
                @endforelse
            </div>
        </div>

        {{-- Drag overlay --}}
        <div x-show="isDragging" x-cloak
             class="pointer-events-none absolute inset-0 z-50 flex items-center justify-center rounded-xl border-2 border-dashed border-primary-500 bg-primary-50/80 dark:bg-primary-900/30">
            <div class="text-center">
                <svg class="mx-auto h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                <p class="mt-2 text-sm font-medium text-primary-600 dark:text-primary-400">Dateien hier ablegen</p>
            </div>
        </div>

        {{-- Main Chat Area --}}
        <div class="ai-chat-column rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Chat Messages --}}
            <div class="chat-messages p-4 sm:p-6" x-ref="chatMessages">
                <div class="mx-auto max-w-3xl space-y-4">
                    {{-- Server-rendered history --}}
                    @forelse($chatMessages as $message)
                        <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm
                                {{ $message['role'] === 'user'
                                    ? 'bg-primary-600 text-white'
                                    : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}">
                                @if($message['role'] === 'assistant')
                                    <div class="prose prose-sm dark:prose-invert max-w-none">
                                        {!! \Illuminate\Support\Str::markdown($message['content']) !!}
                                    </div>
                                @else
                                    @if(!empty($message['attachments']))
                                        <div class="mb-2 flex flex-wrap gap-1.5">
                                            @foreach($message['attachments'] as $attachment)
                                                @if(str_starts_with($attachment['mime_type'] ?? '', 'image/'))
                                                    <img src="{{ $attachment['url'] }}" alt="{{ $attachment['name'] ?? 'Anhang' }}" class="h-20 w-20 rounded-lg object-cover">
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-white/20 px-2 py-1 text-xs">
                                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                                                        {{ $attachment['name'] ?? 'Datei' }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    {{ $message['content'] }}
                                @endif
                            </div>
                        </div>
                    @empty
                        <div x-show="!isStreaming && !streamedText" class="flex items-center justify-center py-20">
                            <div class="text-center">
                                <x-heroicon-o-chat-bubble-left-right class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                                <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Beschreibe deine Website</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Sage mir, was du dir vorstellst – ich erstelle alles für dich.
                                </p>
                            </div>
                        </div>
                    @endforelse

                    {{-- User message (added by JS during streaming) --}}
                    <template x-if="pendingUserMessage || pendingAttachments.length > 0">
                        <div class="flex justify-end">
                            <div class="max-w-[85%] rounded-2xl bg-primary-600 px-4 py-3 text-sm text-white">
                                <template x-if="pendingAttachments.length > 0">
                                    <div class="mb-2 flex flex-wrap gap-1.5">
                                        <template x-for="(att, i) in pendingAttachments" :key="i">
                                            <img x-show="att.preview" :src="att.preview" class="h-20 w-20 rounded-lg object-cover">
                                        </template>
                                    </div>
                                </template>
                                <span x-text="pendingUserMessage"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Thinking/Tool activity (collapsible) --}}
                    <template x-if="thinkingSteps.length > 0">
                        <div class="flex justify-start">
                            <div class="max-w-[85%] w-full">
                                <button @click="thinkingOpen = !thinkingOpen"
                                    class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium transition-colors"
                                    :class="isStreaming && !streamedText
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                        : 'bg-gray-50 text-gray-500 hover:bg-gray-100 dark:bg-gray-800/50 dark:text-gray-400 dark:hover:bg-gray-800'">
                                    {{-- Spinner while actively thinking --}}
                                    <template x-if="isStreaming && !streamedText">
                                        <svg class="h-3.5 w-3.5 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                    {{-- Chevron when done --}}
                                    <template x-if="!isStreaming || streamedText">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0 transition-transform" :class="thinkingOpen ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </template>
                                    <span x-text="isStreaming && !streamedText
                                        ? thinkingSteps[thinkingSteps.length - 1]?.label || 'Denkt nach...'
                                        : thinkingSteps.length + ' Schritt' + (thinkingSteps.length !== 1 ? 'e' : '') + ' ausgeführt'">
                                    </span>
                                </button>
                                <div x-show="thinkingOpen" x-cloak
                                    x-transition:enter="transition-all ease-out duration-200"
                                    x-transition:enter-start="opacity-0 max-h-0"
                                    x-transition:enter-end="opacity-100 max-h-96"
                                    x-transition:leave="transition-all ease-in duration-150"
                                    x-transition:leave-start="opacity-100 max-h-96"
                                    x-transition:leave-end="opacity-0 max-h-0"
                                    class="mt-1 rounded-xl border border-gray-100 bg-gray-50/50 px-3 py-2 dark:border-gray-700/50 dark:bg-gray-800/30">
                                    <template x-for="(step, i) in thinkingSteps" :key="i">
                                        <div class="flex items-start gap-2 py-1 text-xs" :class="i > 0 ? 'border-t border-gray-100 dark:border-gray-700/30' : ''">
                                            <span class="mt-0.5 flex-shrink-0" :class="step.status === 'done' ? 'text-green-500' : step.status === 'running' ? 'text-amber-500' : 'text-gray-400'">
                                                <template x-if="step.status === 'done'">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                </template>
                                                <template x-if="step.status === 'running'">
                                                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                </template>
                                            </span>
                                            <span class="text-gray-600 dark:text-gray-300" x-text="step.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Streaming assistant response --}}
                    <template x-if="isStreaming || streamedText">
                        <div class="flex justify-start">
                            <div class="max-w-[85%] rounded-2xl bg-gray-100 px-4 py-3 text-sm text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                <div class="prose prose-sm dark:prose-invert max-w-none" x-html="renderedMarkdown"></div>
                                <span x-show="isStreaming && !streamedText && thinkingSteps.length === 0" class="flex items-center gap-1 text-xs text-gray-400">
                                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Denkt nach...
                                </span>
                                <span x-show="isStreaming && streamedText" class="inline-block w-1.5 h-4 bg-primary-500 animate-pulse ml-0.5 align-text-bottom rounded-sm"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Input Area --}}
            <div class="chat-input border-t border-gray-100 p-3 dark:border-gray-800 sm:p-4">
                {{-- File previews --}}
                <div x-show="attachments.length > 0" x-cloak class="mx-auto mb-2 flex max-w-3xl flex-wrap gap-2">
                    <template x-for="(file, index) in attachments" :key="index">
                        <div class="group relative h-16 w-16 overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <template x-if="file.type.startsWith('image/')">
                                <img :src="file.preview" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!file.type.startsWith('image/')">
                                <div class="flex h-full flex-col items-center justify-center">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span class="mt-0.5 text-[10px] text-gray-500" x-text="file.name.split('.').pop().toUpperCase()"></span>
                                </div>
                            </template>
                            <button
                                @click="removeAttachment(index)"
                                class="absolute -right-1 -top-1 hidden rounded-full bg-red-500 p-0.5 text-white shadow group-hover:block"
                            >
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <form @submit.prevent="sendMessage" class="relative mx-auto flex max-w-3xl gap-2 sm:gap-3">
                    {{-- @-mention dropdown for media library --}}
                    <div x-show="mentionState.open" x-cloak
                         @click.outside="mentionState.open = false"
                         class="absolute bottom-full left-0 right-0 mb-2 max-h-72 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <div x-show="mentionState.loading && mentionState.items.length === 0" class="px-3 py-2 text-xs text-gray-500">Suche...</div>
                        <div x-show="!mentionState.loading && mentionState.items.length === 0" class="px-3 py-2 text-xs text-gray-500">Keine Medien gefunden</div>
                        <template x-for="(item, i) in mentionState.items" :key="item.id">
                            <button type="button"
                                @click="selectMention(item)"
                                @mouseenter="mentionState.selectedIndex = i"
                                :class="i === mentionState.selectedIndex ? 'bg-primary-50 dark:bg-primary-900/30' : ''"
                                class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700">
                                <img :src="item.thumb_url" class="h-9 w-9 flex-shrink-0 rounded object-cover bg-gray-100 dark:bg-gray-700">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs font-medium text-gray-900 dark:text-white" x-text="item.name"></div>
                                    <div class="truncate text-[10px] text-gray-500 dark:text-gray-400" x-text="item.file_name"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                    {{-- File upload button --}}
                    <label class="inline-flex cursor-pointer items-center rounded-xl border border-gray-300 bg-gray-50 px-2.5 py-2.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition-colors"
                           :class="isStreaming ? 'opacity-50 pointer-events-none' : ''">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                        <input
                            type="file"
                            x-ref="fileInput"
                            @change="handleFileSelect($event)"
                            multiple
                            accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/bmp,image/x-icon"
                            class="hidden"
                            :disabled="isStreaming"
                        >
                    </label>

                    <textarea
                        x-model="prompt"
                        x-ref="promptInput"
                        placeholder="Beschreibe, was du möchtest... (@ für Medien)"
                        rows="1"
                        class="flex-1 resize-none rounded-xl border-gray-300 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                        style="max-height: 10rem"
                        :disabled="isStreaming"
                        autofocus
                        x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; checkMention()"
                        x-on:click="checkMention()"
                        x-on:keyup.arrow-left="checkMention()"
                        x-on:keyup.arrow-right="checkMention()"
                        x-on:keydown="
                            if (handleMentionKeydown($event)) return;
                            if ($event.key === 'Enter') {
                                if ($event.shiftKey) {
                                    $event.preventDefault();
                                    prompt += '\n';
                                    $nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; });
                                } else {
                                    $event.preventDefault();
                                    sendMessage();
                                }
                            }
                        "
                    ></textarea>
                    {{-- Send button --}}
                    <button
                        x-show="!isStreaming"
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition-colors"
                        :disabled="!prompt.trim() && attachments.length === 0"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <span class="hidden sm:inline">Senden</span>
                    </button>

                    {{-- Stop button --}}
                    <button
                        x-show="isStreaming"
                        x-cloak
                        type="button"
                        @click="stopStreaming()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="6" y="6" width="12" height="12" rx="2" />
                        </svg>
                        <span class="hidden sm:inline">Stop</span>
                    </button>
                </form>
            </div>
        </div>
    </div> {{-- /ai-chat-container --}}
    </div> {{-- /ai-chat-root --}}

    @script
    <script>
        Alpine.data('aiChat', () => ({
            prompt: '',
            isStreaming: false,
            streamedText: '',
            pendingUserMessage: '',
            pendingAttachments: [],
            attachments: [],  // {file, name, type, preview}
            mentionedMedia: [], // {id, name, file_name, url, mime_type, thumb_url}
            mentionState: {
                open: false,
                query: '',
                items: [],
                selectedIndex: 0,
                loading: false,
                requestId: 0,
            },
            toolCalls: [],
            thinkingSteps: [],  // {label, status: 'running'|'done'}
            thinkingOpen: false,
            currentSessionId: @js($sessionId),
            isDragging: false,
            abortController: null,

            toolLabels: {
                'CreatePage': 'Seite erstellen',
                'UpdatePage': 'Seite aktualisieren',
                'DeletePage': 'Seite löschen',
                'CreateMenu': 'Menü erstellen',
                'UpdateMenu': 'Menü aktualisieren',
                'SetSetting': 'Einstellung setzen',
                'SetLayout': 'Layout ändern',
                'SetLayoutHtml': 'Layout-HTML setzen',
                'GetSiteState': 'Website-Zustand laden',
                'GetPageContent': 'Seiteninhalt laden',
                'SetHeaderFooter': 'Header/Footer setzen',
                'RollbackPage': 'Seite zurücksetzen',
                'ListMedia': 'Medien durchsuchen',
                'GetMediaUrl': 'Medien-URL abrufen',
            },

            init() {
                this.$wire.on('stream-finished', () => {
                    this.streamedText = '';
                    this.pendingUserMessage = '';
                    this.pendingAttachments = [];
                    this.toolCalls = [];
                    this.thinkingSteps = [];
                    this.thinkingOpen = false;
                    this.scrollToBottom();
                    this.focusInput();
                });

                this.$wire.on('session-loaded', () => {
                    this.scrollToBottom();
                    this.focusInput();
                });

                // Drag & drop on the chat area
                const chatArea = this.$el;
                chatArea.addEventListener('dragover', (e) => { e.preventDefault(); this.isDragging = true; });
                chatArea.addEventListener('dragleave', (e) => {
                    if (!chatArea.contains(e.relatedTarget)) this.isDragging = false;
                });
                chatArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    this.isDragging = false;
                    if (e.dataTransfer?.files?.length) {
                        this.addFiles(e.dataTransfer.files);
                    }
                });
            },

            handleFileSelect(event) {
                this.addFiles(event.target.files);
                event.target.value = '';
            },

            addFiles(fileList) {
                for (const file of fileList) {
                    const entry = { file, name: file.name, type: file.type, preview: null };
                    if (file.type.startsWith('image/')) {
                        entry.preview = URL.createObjectURL(file);
                    }
                    this.attachments.push(entry);
                }
            },

            async checkMention() {
                const textarea = this.$refs.promptInput;
                if (!textarea) return;
                const cursor = textarea.selectionStart;
                const before = this.prompt.slice(0, cursor);
                // Match @ at start or after whitespace, followed by non-space/non-@ chars
                const match = before.match(/(?:^|\s)@([^\s@]*)$/);
                if (!match) {
                    this.mentionState.open = false;
                    return;
                }
                const query = match[1];
                this.mentionState.query = query;
                this.mentionState.open = true;
                this.mentionState.loading = true;
                const reqId = ++this.mentionState.requestId;
                try {
                    const url = '{{ route("admin.ai-chat.media-search") }}?q=' + encodeURIComponent(query);
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (reqId !== this.mentionState.requestId) return;
                    const items = await res.json();
                    this.mentionState.items = Array.isArray(items) ? items : [];
                    this.mentionState.selectedIndex = 0;
                } catch (e) {
                    this.mentionState.items = [];
                } finally {
                    if (reqId === this.mentionState.requestId) {
                        this.mentionState.loading = false;
                    }
                }
            },

            selectMention(item) {
                const textarea = this.$refs.promptInput;
                if (!textarea) return;
                const cursor = textarea.selectionStart;
                const before = this.prompt.slice(0, cursor);
                const after = this.prompt.slice(cursor);
                // Replace the trailing @query with @name (sanitized — no spaces)
                const safeName = (item.name || item.file_name || ('media-' + item.id)).replace(/\s+/g, '_');
                const newBefore = before.replace(/@([^\s@]*)$/, '@' + safeName + ' ');
                this.prompt = newBefore + after;
                if (!this.mentionedMedia.find(m => m.id === item.id)) {
                    this.mentionedMedia.push(item);
                }
                this.mentionState.open = false;
                this.$nextTick(() => {
                    textarea.focus();
                    const pos = newBefore.length;
                    textarea.setSelectionRange(pos, pos);
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                });
            },

            handleMentionKeydown(e) {
                if (!this.mentionState.open) return false;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.mentionState.open = false;
                    return true;
                }
                if (this.mentionState.items.length === 0) return false;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.mentionState.selectedIndex = (this.mentionState.selectedIndex + 1) % this.mentionState.items.length;
                    return true;
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const len = this.mentionState.items.length;
                    this.mentionState.selectedIndex = (this.mentionState.selectedIndex - 1 + len) % len;
                    return true;
                }
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    this.selectMention(this.mentionState.items[this.mentionState.selectedIndex]);
                    return true;
                }
                return false;
            },

            removeAttachment(index) {
                if (this.attachments[index]?.preview) {
                    URL.revokeObjectURL(this.attachments[index].preview);
                }
                this.attachments.splice(index, 1);
            },

            get renderedMarkdown() {
                if (!this.streamedText) return '';
                let html = this.streamedText
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                    .replace(/`([^`]+)`/g, '<code class="bg-gray-200 dark:bg-gray-700 px-1 rounded text-sm">$1</code>')
                    .replace(/\n\n/g, '</p><p>')
                    .replace(/\n/g, '<br>');
                return '<p>' + html + '</p>';
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.chatMessages;
                    if (el) el.scrollTop = el.scrollHeight;
                    setTimeout(() => { if (el) el.scrollTop = el.scrollHeight; }, 50);
                });
            },

            focusInput() {
                setTimeout(() => { this.$refs.promptInput?.focus(); }, 100);
            },

            stopStreaming() {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.isStreaming = false;
                if (this.streamedText) {
                    $wire.call('streamCompleted', this.currentSessionId, this.streamedText + '\n\n*(Abgebrochen)*');
                }
                this.focusInput();
            },

            addSessionToSidebar(id, title) {
                const list = this.$refs.sessionList;
                if (!list) return;

                // Don't add if already present
                if (list.querySelector(`[data-session-id="${id}"]`)) return;

                // Deselect current active session
                list.querySelectorAll('button').forEach(btn => {
                    btn.classList.remove('bg-primary-50', 'text-primary-700', 'dark:bg-primary-900/50', 'dark:text-primary-400');
                    btn.classList.add('text-gray-700', 'hover:bg-gray-50', 'dark:text-gray-300', 'dark:hover:bg-gray-800');
                });

                // Remove empty state
                const emptyMsg = list.querySelector('p');
                if (emptyMsg) emptyMsg.remove();

                const btn = document.createElement('button');
                btn.setAttribute('data-session-id', id);
                btn.className = 'w-full rounded-lg px-3 py-2 text-left text-sm transition-colors bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-400';
                btn.innerHTML = `<div class="truncate font-medium">${title || 'Unbenannt'}</div><div class="truncate text-xs text-gray-500 dark:text-gray-400">Gerade eben</div>`;
                btn.addEventListener('click', () => $wire.call('loadSession', id));

                list.prepend(btn);
            },

            async sendMessage() {
                const text = this.prompt.trim();
                if ((!text && this.attachments.length === 0 && this.mentionedMedia.length === 0) || this.isStreaming) return;

                // Only keep mentions that are still referenced in the text
                const activeMentions = this.mentionedMedia.filter(m => {
                    const safeName = (m.name || m.file_name || ('media-' + m.id)).replace(/\s+/g, '_');
                    return text.includes('@' + safeName);
                });

                this.pendingUserMessage = text;
                this.pendingAttachments = [
                    ...this.attachments,
                    ...activeMentions.map(m => ({ name: m.file_name, type: m.mime_type, preview: m.thumb_url || m.url })),
                ];
                this.prompt = '';
                this.mentionState.open = false;
                this.isStreaming = true;
                this.streamedText = '';
                this.toolCalls = [];
                this.thinkingSteps = [];
                this.thinkingOpen = false;
                this.abortController = new AbortController();
                this.scrollToBottom();

                const formData = new FormData();
                formData.append('prompt', text);
                if (this.currentSessionId) {
                    formData.append('session_id', this.currentSessionId);
                }
                for (const att of this.attachments) {
                    formData.append('attachments[]', att.file);
                }
                for (const m of activeMentions) {
                    formData.append('mentioned_media_ids[]', m.id);
                }
                this.attachments = [];
                this.mentionedMedia = [];

                try {
                    const response = await fetch('{{ route("admin.ai-chat.stream") }}', {
                        method: 'POST',
                        signal: this.abortController.signal,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                || '{{ csrf_token() }}',
                            'Accept': 'text/event-stream',
                        },
                        body: formData,
                    });

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop() || '';

                        for (const line of lines) {
                            if (!line.startsWith('data: ')) continue;
                            const jsonStr = line.slice(6).trim();
                            if (!jsonStr || jsonStr === '[DONE]') continue;

                            try {
                                const data = JSON.parse(jsonStr);

                                if (data.type === 'session') {
                                    this.currentSessionId = data.id;
                                    this.addSessionToSidebar(data.id, text);
                                    $wire.call('addUserMessage', data.id, text, data.attachments || []);
                                } else if (data.type === 'text') {
                                    // First text after tools → collapse thinking
                                    if (!this.streamedText && this.thinkingSteps.length > 0) {
                                        this.thinkingOpen = false;
                                    }
                                    this.streamedText += data.delta;
                                    this.scrollToBottom();
                                } else if (data.type === 'tool_call') {
                                    // Mark previous step as done
                                    const prev = this.thinkingSteps.findLast(s => s.status === 'running');
                                    if (prev) prev.status = 'done';
                                    // Add new step
                                    const label = this.toolLabels[data.name] || data.name.replace(/_/g, ' ');
                                    this.thinkingSteps.push({ label, status: 'running' });
                                    this.thinkingOpen = true;
                                    this.toolCalls.push(data.name);
                                    this.scrollToBottom();
                                } else if (data.type === 'tool_result') {
                                    // Mark matching step as done
                                    const step = this.thinkingSteps.findLast(s => s.status === 'running');
                                    if (step) step.status = 'done';
                                } else if (data.type === 'done') {
                                    // Mark all remaining as done
                                    this.thinkingSteps.forEach(s => { s.status = 'done'; });
                                    $wire.call('streamCompleted', this.currentSessionId, this.streamedText);
                                } else if (data.type === 'error') {
                                    this.thinkingSteps.forEach(s => { s.status = 'done'; });
                                    this.streamedText = data.message;
                                }
                            } catch (e) {
                                // Skip malformed JSON
                            }
                        }
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        this.streamedText = 'Verbindungsfehler: ' + error.message;
                    }
                }

                this.abortController = null;
                this.isStreaming = false;
                this.pendingAttachments.forEach(a => { if (a.preview) URL.revokeObjectURL(a.preview); });
                this.focusInput();
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
