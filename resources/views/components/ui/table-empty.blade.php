@props([
    'colspan' => 1,
    'message' => 'لا توجد سجلات حالياً.',
    'hint' => null,
    'icon' => '📋',
])

{{--
    Standard empty-state row for data tables (DESIGN_SYSTEM.md §60 — no blank
    screens). Spans the full table width so the message stays centred under the
    header row regardless of column count.

    Usage:
        @forelse ($rows as $row)
            ...
        @empty
            <x-ui.table-empty :colspan="6" message="لا توجد سجلات حضور بعد." />
        @endforelse
--}}
<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-12 text-center">
        <div class="flex flex-col items-center justify-center gap-2">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mist-100 text-2xl dark:bg-ink-800" aria-hidden="true">
                {{ $icon }}
            </span>
            <p class="text-sm font-medium text-ink-700 dark:text-mist-200">{{ $message }}</p>
            @if ($hint)
                <p class="text-xs text-mist-500 dark:text-mist-400">{{ $hint }}</p>
            @endif
        </div>
    </td>
</tr>
