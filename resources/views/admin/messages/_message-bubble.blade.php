@php
    $isAdmin = (bool) ($message['is_admin'] ?? false);
@endphp

<div class="flex items-end gap-2 {{ $isAdmin ? 'flex-row-reverse' : 'flex-row' }}" data-message-id="{{ $message['id'] }}">
    <img
        src="{{ $message['avatar_url'] }}"
        alt="{{ $message['sender_name'] }}"
        class="h-8 w-8 shrink-0 rounded-full border border-slate-700 object-cover"
    >
    <div class="max-w-[80%]">
        <div @class([
            'px-4 py-3 text-sm shadow-sm rounded-2xl',
            'bg-emerald-400 text-emerald-900 rounded-se-none' => $isAdmin,
            'border border-mist-200 bg-white text-ink-700 rounded-ss-none dark:border-ink-700 dark:bg-ink-800 dark:text-mist-100' => ! $isAdmin,
        ])>
            {{ $message['body'] }}
        </div>
        <div class="mt-1 flex items-center gap-1.5 px-1 text-[11px] text-mist-400 dark:text-mist-500 {{ $isAdmin ? 'justify-end' : 'justify-start' }}">
            <span>
                {{ $message['sender_name'] }} ·
                <span class="veyra-relative-time" data-timestamp="{{ $message['created_at'] }}">
                    {{ ! empty($message['created_at']) ? \Illuminate\Support\Carbon::parse($message['created_at'])->diffForHumans() : '' }}
                </span>
            </span>
            @include('admin.messages._receipt', ['status' => $message['receipt'], 'onDark' => $isAdmin])
        </div>
    </div>
</div>
