{{-- Simple (previous / next only) pagination, matching tailwind.blade.php. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="التنقّل بين الصفحات" class="flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="ui-btn ui-btn-secondary ui-btn-sm" aria-disabled="true">السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="ui-btn ui-btn-secondary ui-btn-sm">السابق</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="ui-btn ui-btn-secondary ui-btn-sm">التالي</a>
        @else
            <span class="ui-btn ui-btn-secondary ui-btn-sm" aria-disabled="true">التالي</span>
        @endif
    </nav>
@endif
