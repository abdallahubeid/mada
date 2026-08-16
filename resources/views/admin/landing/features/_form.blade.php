@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $image = $feature->relationLoaded('images')
        ? $feature->images->firstWhere('collection', 'icon')
        : $feature->image('icon')->first();
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label class="{{ $labelClass }}">العنوان</label>
        <input type="text" name="title" value="{{ old('title', $feature->title) }}" class="{{ $inputClass }}" required>
    </div>

    <div>
        <label class="{{ $labelClass }}">الوصف</label>
        <textarea name="description" rows="4" class="{{ $inputClass }}" required>{{ old('description', $feature->description) }}</textarea>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}">أيقونة Phosphor</label>
            <input type="text" dir="ltr" name="icon" value="{{ old('icon', $feature->icon) }}" class="{{ $inputClass }}" placeholder="ph:shield-check-bold">
        </div>
        <div>
            <label class="{{ $labelClass }}">ترتيب العرض</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $feature->sort_order) }}" class="{{ $inputClass }}">
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $feature->is_published)) class="rounded border-mist-300 text-brand-500 focus:ring-brand-500">
        منشور على الموقع
    </label>

    <div>
        <label class="{{ $labelClass }}">صورة الأيقونة (اختياري)</label>
        @if ($image)
            <div class="mb-3 flex items-center gap-3">
                <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}" class="h-14 w-14 rounded-xl object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                <p class="text-xs text-mist-500">{{ $image->path }}</p>
            </div>
        @endif
        <input type="file" name="icon_image" accept="image/*" class="{{ $inputClass }}">
        <input type="text" name="alt_text" value="{{ old('alt_text', $image?->alt_text) }}" placeholder="نص بديل للصورة" class="{{ $inputClass }} mt-2">
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.features.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow">حفظ</button>
    </div>
</form>
