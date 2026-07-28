@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $avatar = $testimonial->relationLoaded('images')
        ? ($testimonial->images->firstWhere('collection', 'avatar') ?? $testimonial->images->firstWhere('collection', 'logo'))
        : ($testimonial->image('avatar')->first() ?? $testimonial->image('logo')->first());
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label class="{{ $labelClass }}">الاقتباس</label>
        <textarea name="quote" rows="4" class="{{ $inputClass }}" required>{{ old('quote', $testimonial->quote) }}</textarea>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}">اسم العميل</label>
            <input type="text" name="client_name" value="{{ old('client_name', $testimonial->client_name) }}" class="{{ $inputClass }}" required>
        </div>
        <div>
            <label class="{{ $labelClass }}">المسمى الوظيفي</label>
            <input type="text" name="client_role" value="{{ old('client_role', $testimonial->client_role) }}" class="{{ $inputClass }}">
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}">المؤسسة</label>
            <input type="text" name="organization_name" value="{{ old('organization_name', $testimonial->organization_name) }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="{{ $labelClass }}">التقييم</label>
            <input type="number" min="1" max="5" name="rate" value="{{ old('rate', $testimonial->rate) }}" class="{{ $inputClass }}">
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}">ترتيب العرض</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $testimonial->sort_order) }}" class="{{ $inputClass }}">
        </div>
        <label class="mt-7 flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $testimonial->is_published)) class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400">
            منشور على الموقع
        </label>
    </div>
    <div>
        <label class="{{ $labelClass }}">الصورة الشخصية / الشعار</label>
        @if ($avatar)
            <div class="mb-3 flex items-center gap-3">
                <img src="{{ $avatar->url() }}" alt="{{ $avatar->alt_text }}" class="h-14 w-14 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                <p class="text-xs text-mist-500">{{ $avatar->path }}</p>
            </div>
        @endif
        <input type="file" name="avatar" accept="image/*" class="{{ $inputClass }}">
        <input type="text" name="alt_text" value="{{ old('alt_text', $avatar?->alt_text) }}" placeholder="نص بديل للصورة" class="{{ $inputClass }} mt-2">
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.testimonials.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow">حفظ</button>
    </div>
</form>
