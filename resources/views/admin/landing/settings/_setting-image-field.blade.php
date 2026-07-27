@php
    /** @var callable(string): string $val */
    $path = $val($key);
    $url = filled($path) ? \App\Models\Setting::assetUrl($path) : null;
@endphp

<div class="setting-image-field" data-setting-key="{{ $key }}">
    <label class="{{ $labelClass }}">{{ $label }}</label>

    @if ($url)
        <div
            class="setting-image-preview mb-3 transition-opacity duration-300"
            data-preview-wrapper
        >
            <div class="inline-flex items-start gap-3">
                <div class="relative shrink-0">
                    <div class="rounded-lg border border-slate-200 bg-slate-100/80 p-2 dark:border-slate-700 dark:bg-slate-800/50">
                        <img
                            src="{{ $url }}"
                            alt="{{ $label }} preview"
                            class="{{ $previewClass }}"
                        >
                    </div>
                    <button
                        type="button"
                        class="setting-image-delete absolute -top-2 -end-2 flex h-8 w-8 items-center justify-center rounded-full border border-mist-200 bg-white text-mist-500 shadow-sm transition hover:border-danger-solid/40 hover:bg-danger-solid/10 hover:text-danger-solid dark:border-ink-600 dark:bg-ink-800 dark:text-mist-400 dark:hover:border-danger-solid/50 dark:hover:text-danger-solid"
                        title="حذف الصورة"
                        aria-label="حذف الصورة"
                        data-delete-url="{{ route('admin.landing.settings.image.destroy', ['key' => $key]) }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-mist-500" dir="ltr" data-path-label>{{ $path }}</p>
            </div>
        </div>
    @endif

    <input
        type="file"
        name="{{ $key }}"
        accept="{{ $accept }}"
        class="{{ $inputClass }}"
        data-file-input
    >
</div>
