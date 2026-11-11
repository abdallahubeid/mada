@php
    $inputClass = 'ui-control w-full';
    $labelClass = 'ui-label';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
@endphp

<form method="POST" action="{{ $action }}" class="ui-card space-y-4 p-4">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="title" class="{{ $labelClass }}">المسمى الوظيفي</label>
        <input id="title" type="text" name="title" value="{{ old('title', $job->title) }}" required class="{{ $inputClass }}">
        @error('title')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="department_id" class="{{ $labelClass }}">القسم</label>
            <select id="department_id" name="department_id" class="{{ $inputClass }}">
                <option value="">— بدون —</option>
                @foreach ($departments as $id => $name)
                    <option value="{{ $id }}" @selected((string) old('department_id', $job->department_id) === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('department_id')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="employment_type" class="{{ $labelClass }}">نوع التوظيف</label>
            <select id="employment_type" name="employment_type" required class="{{ $inputClass }}">
                @foreach ($employmentTypes as $type)
                    <option value="{{ $type->value }}" @selected(old('employment_type', $job->employment_type?->value ?? $job->employment_type) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('employment_type')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="location" class="{{ $labelClass }}">الموقع</label>
            <input id="location" type="text" name="location" value="{{ old('location', $job->location) }}" class="{{ $inputClass }}" placeholder="الرياض · حضوري">
            @error('location')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="salary_range" class="{{ $labelClass }}">نطاق الراتب</label>
            <input id="salary_range" type="text" name="salary_range" value="{{ old('salary_range', $job->salary_range) }}" class="{{ $inputClass }}" placeholder="اختياري">
            @error('salary_range')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="description" class="{{ $labelClass }}">الوصف</label>
        <textarea id="description" name="description" rows="5" required class="{{ $inputClass }}">{{ old('description', $job->description) }}</textarea>
        @error('description')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="requirements" class="{{ $labelClass }}">المتطلبات (سطر لكل بند)</label>
        <textarea id="requirements" name="requirements" rows="4" class="{{ $inputClass }}">{{ old('requirements', $job->requirements) }}</textarea>
        @error('requirements')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="{{ $labelClass }}">الحالة</label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $job->status?->value ?? $job->status) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('hr.jobs.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600">إلغاء</a>
        <button type="submit" class="ui-btn ui-btn-primary">حفظ</button>
    </div>
</form>
