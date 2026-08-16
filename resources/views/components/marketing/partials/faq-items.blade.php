@foreach ($items as $i => $item)
    @php
        $question = is_array($item) ? $item['question'] : $item->question;
        $answer = is_array($item) ? $item['answer'] : $item->answer;
    @endphp

    {{--
        `mada-surface` without its hover lift — an accordion row that rises
        under the cursor fights the click it is inviting. The open row is
        marked with an inline-start accent rail instead, which is the same
        active-state device the app sidebar uses.
    --}}
    <div
        class="mada-surface overflow-hidden !transform-none"
        :class="open === {{ $i }} && 'ring-brand-500/20'"
    >
        <button
            type="button"
            @click="open === {{ $i }} ? open = null : open = {{ $i }}"
            class="flex w-full items-center justify-between gap-5 px-6 py-5 text-start transition duration-150 hover:bg-mist-50 sm:px-7 sm:py-6"
            :aria-expanded="open === {{ $i }} ? 'true' : 'false'"
        >
            <span class="flex min-w-0 items-center gap-4">
                {{-- The rail: 2px, scales on the block axis, mirrors for free via inset-inline-start. --}}
                <span
                    class="h-6 w-0.5 shrink-0 rounded-full bg-brand-500 transition-transform duration-200 ease-out"
                    :class="open === {{ $i }} ? 'scale-y-100' : 'scale-y-0'"
                    aria-hidden="true"
                ></span>
                <span class="font-display text-lg font-bold tracking-tight text-ink-900 sm:text-xl">{{ $question }}</span>
            </span>

            <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-mist-100 text-mist-500 transition duration-200"
                :class="open === {{ $i }} && 'rotate-180 bg-brand-500/10 text-brand-600'"
                aria-hidden="true"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
        </button>

        <div x-show="open === {{ $i }}" x-collapse x-cloak>
            {{-- `ps-12` aligns the answer with the question text, past the rail. --}}
            <p class="px-6 pb-6 text-base leading-[1.8] text-mist-600 sm:px-7 sm:pb-7 sm:ps-[4.5rem]">{{ $answer }}</p>
        </div>
    </div>
@endforeach
