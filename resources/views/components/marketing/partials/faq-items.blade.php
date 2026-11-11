{{--
    Template accordion: a rounded, hairline-bordered item with a cream header
    row and a chevron at its end that turns when open. One item open at a
    time (the `open` index lives on the wrapper in faq-accordion).
--}}
@foreach ($items as $i => $item)
    @php
        $question = is_array($item) ? $item['question'] : $item->question;
        $answer = is_array($item) ? $item['answer'] : $item->answer;
        $panelId = 'faq-'.substr(md5($question), 0, 10);
    @endphp

    <div class="site-accordion">
        <button
            type="button"
            class="site-accordion-head"
            @click="open === {{ $i }} ? open = null : open = {{ $i }}"
            :aria-expanded="open === {{ $i }} ? 'true' : 'false'"
            aria-controls="{{ $panelId }}"
        >
            {{ $question }}
            <svg viewBox="0 0 512.011 512.011" aria-hidden="true">
                <path fill="currentColor" d="M505.755,123.592c-8.341-8.341-21.824-8.341-30.165,0L256.005,343.176L36.421,123.592c-8.341-8.341-21.824-8.341-30.165,0 s-8.341,21.824,0,30.165l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251l234.667-234.667 C514.096,145.416,514.096,131.933,505.755,123.592z" />
            </svg>
        </button>
        <div id="{{ $panelId }}" x-show="open === {{ $i }}" x-collapse x-cloak>
            <p class="px-4 py-3">{{ $answer }}</p>
        </div>
    </div>
@endforeach
