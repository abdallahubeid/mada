@foreach ($items as $i => $item)
    <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white transition duration-200 dark:border-ink-800 dark:bg-ink-800/40">
        <button
            type="button"
            @click="open === {{ $i }} ? open = null : open = {{ $i }}"
            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-start"
            :aria-expanded="open === {{ $i }}"
        >
            <span class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">{{ $item['question'] }}</span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 shrink-0 text-emerald-500 transition-transform duration-300"
                :class="open === {{ $i }} && 'rotate-180'"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <div x-show="open === {{ $i }}" x-collapse x-cloak>
            <p class="px-5 pb-5 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $item['answer'] }}</p>
        </div>
    </div>
@endforeach
