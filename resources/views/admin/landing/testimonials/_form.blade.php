@php
    $inputClass = 'ui-control w-full';
    $labelClass = 'ui-label';
    $avatar = $testimonial->relationLoaded('images')
        ? ($testimonial->images->firstWhere('collection', 'avatar') ?? $testimonial->images->firstWhere('collection', 'logo'))
        : ($testimonial->image('avatar')->first() ?? $testimonial->image('logo')->first());
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="ui-card space-y-4 p-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="f--form-quote-1" class="{{ $labelClass }}">الاقتباس</label>
        <textarea id="f--form-quote-1" name="quote" rows="4" class="{{ $inputClass }}" required>{{ old('quote', $testimonial->quote) }}</textarea>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="f--form-client-name-2" class="{{ $labelClass }}">اسم العميل</label>
            <input id="f--form-client-name-2" type="text" name="client_name" value="{{ old('client_name', $testimonial->client_name) }}" class="{{ $inputClass }}" required>
        </div>
        <div>
            <label for="f--form-client-role-3" class="{{ $labelClass }}">المسمى الوظيفي</label>
            <input id="f--form-client-role-3" type="text" name="client_role" value="{{ old('client_role', $testimonial->client_role) }}" class="{{ $inputClass }}">
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="f--form-organization-name-4" class="{{ $labelClass }}">المؤسسة</label>
            <input id="f--form-organization-name-4" type="text" name="organization_name" value="{{ old('organization_name', $testimonial->organization_name) }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label for="f--form-rate-5" class="{{ $labelClass }}">التقييم</label>
            <input id="f--form-rate-5" type="number" min="1" max="5" name="rate" value="{{ old('rate', $testimonial->rate) }}" class="{{ $inputClass }}">
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="f--form-sort-order-6" class="{{ $labelClass }}">ترتيب العرض</label>
            <input id="f--form-sort-order-6" type="number" name="sort_order" value="{{ old('sort_order', $testimonial->sort_order) }}" class="{{ $inputClass }}">
        </div>
        <label class="mt-7 flex items-center gap-2 text-sm text-ink-700 dark:text-mist-200">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $testimonial->is_published)) class="rounded border-mist-300 text-brand-500 focus:ring-brand-500">
            منشور على الموقع
        </label>
    </div>
    <div>
        <label for="f-avatar" class="{{ $labelClass }}">الصورة الشخصية / الشعار</label>
        @if ($avatar)
            <div class="mb-3 flex items-center gap-3">
                <img src="{{ $avatar->url() }}" alt="{{ $avatar->alt_text }}" class="h-14 w-14 rounded-full object-cover ring-1 ring-mist-200 dark:ring-ink-600">
                <p class="text-xs text-mist-500">{{ $avatar->path }}</p>
            </div>
        @endif
        <input id="f-avatar" type="file" name="avatar" accept="image/*" class="{{ $inputClass }}">
        <input type="text" name="alt_text" value="{{ old('alt_text', $avatar?->alt_text) }}" placeholder="نص بديل للصورة" class="{{ $inputClass }} mt-2">
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.testimonials.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="ui-btn ui-btn-primary">حفظ</button>
    </div>
</form>
