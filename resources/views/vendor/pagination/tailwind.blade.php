{{--
    Pagination for every paginated table (docs/DESIGN_SYSTEM.md §8): an
    Arabic range summary, then page buttons on the shared control metrics —
    white with a hairline, the current page filled with the primary orange.

    Arrows are drawn for left-to-right and flipped under RTL, so "previous"
    always points back along the reading direction.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="التنقّل بين الصفحات" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-mist-500">
            @if ($paginator->firstItem())
                عرض
                <span class="font-semibold text-ink-900 tabular-nums">{{ $paginator->firstItem() }}</span>
                إلى
                <span class="font-semibold text-ink-900 tabular-nums">{{ $paginator->lastItem() }}</span>
            @else
                عرض <span class="font-semibold text-ink-900 tabular-nums">{{ $paginator->count() }}</span>
            @endif
            من
            <span class="font-semibold text-ink-900 tabular-nums">{{ $paginator->total() }}</span>
            نتيجة
        </p>

        <ul class="flex flex-wrap items-center gap-1.5">
            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="flex h-9 min-w-9 items-center justify-center rounded-md border border-mist-200 bg-mist-100 px-2 text-mist-400" aria-disabled="true" aria-label="الصفحة السابقة">
                        <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex h-9 min-w-9 items-center justify-center rounded-md border border-mist-300 bg-white px-2 text-ink-900 transition hover:border-brand-500 hover:text-brand-600" aria-label="الصفحة السابقة">
                        <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                @endif
            </li>

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="flex h-9 min-w-9 items-center justify-center px-1 text-mist-400" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="flex h-9 min-w-9 items-center justify-center rounded-md bg-brand-action px-2 font-semibold text-white tabular-nums" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="flex h-9 min-w-9 items-center justify-center rounded-md border border-mist-300 bg-white px-2 font-medium text-ink-900 tabular-nums transition hover:border-brand-500 hover:text-brand-600" aria-label="الصفحة {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex h-9 min-w-9 items-center justify-center rounded-md border border-mist-300 bg-white px-2 text-ink-900 transition hover:border-brand-500 hover:text-brand-600" aria-label="الصفحة التالية">
                        <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                @else
                    <span class="flex h-9 min-w-9 items-center justify-center rounded-md border border-mist-200 bg-mist-100 px-2 text-mist-400" aria-disabled="true" aria-label="الصفحة التالية">
                        <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
