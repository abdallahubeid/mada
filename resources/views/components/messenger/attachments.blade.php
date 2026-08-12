@props([
    /**
     * Either a PHP collection of MessageAttachment (server-rendered bubbles)
     * or a JS expression string naming an array of descriptors (live bubbles
     * inside x-for). Two shapes, one component — same reasoning as
     * message-menu: the alternative is a hand-written JS twin that drifts.
     */
    'items' => null,
    'expr' => null,
    'mine' => 'false',
])

{{--
    Attachments inside a message bubble.

    ── IMAGES ARE LINKS, NOT JUST PICTURES ─────────────────────────────────
    The <img> points at the preview route and the surrounding <a> points at
    the download route, so a click opens the full file and a long-press offers
    "save". Both routes re-check membership, so neither is a bypass.

    `aspect-[4/3]` + `object-cover` fixes the box before the image loads,
    which stops the thread from jumping as photos arrive — the one layout bug
    that makes a chat unusable while scrolling.

    RTL: the cards use logical spacing (`gap`, `ps`/`pe`, `text-start`) and
    the download glyph is mirrored with `rtl:-scale-x-100` only where it
    encodes direction.
--}}

@if ($expr !== null)
    {{-- Live bubbles: descriptors arriving over Reverb or from the send response. --}}
    <div class="mt-1.5 space-y-1.5" x-show="({{ $expr }} ?? []).length > 0" x-cloak>
        <template x-for="file in ({{ $expr }} ?? [])" :key="file.id">
            <div>
                <template x-if="file.kind === 'image'">
                    <a :href="file.download_url" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl">
                        <img
                            :src="file.preview_url"
                            :alt="file.name"
                            loading="lazy"
                            class="aspect-[4/3] w-full max-w-[16rem] rounded-xl object-cover transition duration-200 hover:opacity-90"
                        >
                    </a>
                </template>

                <template x-if="file.kind !== 'image'">
                    <a
                        :href="file.download_url"
                        class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 transition duration-200"
                        :class="{{ $mine }}
                            ? 'bg-emerald-900/10 hover:bg-emerald-900/20'
                            : 'bg-black/5 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10'"
                    >
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            :class="{{ $mine }} ? 'bg-emerald-900/15 text-emerald-900' : 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-400'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-semibold" :class="{{ $mine }} ? 'text-emerald-950' : 'text-ink-900 dark:text-ink-50'" x-text="file.name"></span>
                            <span class="block text-[10px]" :class="{{ $mine }} ? 'text-emerald-900' : 'text-mist-500 dark:text-mist-400'" x-text="file.size"></span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" :class="{{ $mine }} ? 'text-emerald-900' : 'text-mist-500 dark:text-mist-400'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    </a>
                </template>
            </div>
        </template>
    </div>
@elseif ($items !== null && $items->isNotEmpty())
    <div class="mt-1.5 space-y-1.5">
        @foreach ($items as $file)
            @if ($file->isImage())
                <a
                    href="{{ route('tenant.messenger.attachments.download', $file->id) }}"
                    target="_blank"
                    rel="noopener"
                    class="block overflow-hidden rounded-xl"
                    title="{{ $file->original_name }}"
                >
                    <img
                        src="{{ route('tenant.messenger.attachments.preview', $file->id) }}"
                        alt="{{ $file->original_name }}"
                        loading="lazy"
                        class="aspect-[4/3] w-full max-w-[16rem] rounded-xl object-cover transition duration-200 hover:opacity-90"
                    >
                </a>
            @else
                <a
                    href="{{ route('tenant.messenger.attachments.download', $file->id) }}"
                    @class([
                        'flex items-center gap-2.5 rounded-xl px-2.5 py-2 transition duration-200',
                        'bg-emerald-900/10 hover:bg-emerald-900/20' => $mine === 'true',
                        'bg-black/5 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10' => $mine !== 'true',
                    ])
                    data-testid="messenger-attachment-file"
                >
                    <span @class([
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-emerald-900/15 text-emerald-900' => $mine === 'true',
                        'bg-emerald-400/15 text-emerald-700 dark:text-emerald-400' => $mine !== 'true',
                    ])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span @class([
                            'block truncate text-xs font-semibold',
                            'text-emerald-950' => $mine === 'true',
                            'text-ink-900 dark:text-ink-50' => $mine !== 'true',
                        ])>{{ $file->original_name }}</span>
                        <span @class([
                            'block text-[10px]',
                            'text-emerald-900' => $mine === 'true',
                            'text-mist-500 dark:text-mist-400' => $mine !== 'true',
                        ])>{{ $file->humanSize() }}</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" @class([
                        'h-4 w-4 shrink-0',
                        'text-emerald-900' => $mine === 'true',
                        'text-mist-500 dark:text-mist-400' => $mine !== 'true',
                    ]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                </a>
            @endif
        @endforeach
    </div>
@endif
