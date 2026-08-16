@props([
    'name' => 'permissions[]',
    'value',
    'label',
    'checked' => false,
    'disabled' => false,
])

<label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-mist-200 px-3 py-2 transition hover:border-brand-300 dark:border-ink-600 dark:hover:border-brand-500/40 {{ $disabled ? 'opacity-60' : '' }}">
    <span class="text-sm text-ink-700 dark:text-mist-200">{{ $label }}</span>
    <span class="relative inline-flex shrink-0">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            class="peer sr-only"
            @checked($checked)
            @disabled($disabled)
        >
        <span class="h-6 w-11 rounded-full bg-mist-200 transition peer-checked:bg-brand-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40 dark:bg-ink-700"></span>
        <span class="pointer-events-none absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
    </span>
</label>
