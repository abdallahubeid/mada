@php
    $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
    $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClass = 'mt-1.5 text-xs text-danger-solid';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="{{ $labelClass }}">اسم القسم</label>
        <input id="name" type="text" name="name" value="{{ old('name', $department->name) }}" required class="{{ $inputClass }}">
        @error('name')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="code" class="{{ $labelClass }}">الرمز</label>
        <input id="code" type="text" name="code" dir="ltr" value="{{ old('code', $department->code) }}" class="{{ $inputClass }}" placeholder="HR">
        @error('code')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="{{ $labelClass }}">الوصف</label>
        <textarea id="description" name="description" rows="3" class="{{ $inputClass }}">{{ old('description', $department->description) }}</textarea>
        @error('description')
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="parent_id" class="{{ $labelClass }}">القسم الأب</label>
            <select id="parent_id" name="parent_id" class="{{ $inputClass }}">
                <option value="">— بدون —</option>
                @foreach ($parents as $id => $name)
                    <option value="{{ $id }}" @selected((string) old('parent_id', $department->parent_id) === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('parent_id')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="department_head_id" class="{{ $labelClass }}">رئيس القسم</label>
            <select id="department_head_id" name="department_head_id" class="{{ $inputClass }}">
                <option value="">— بدون —</option>
                @foreach ($heads as $id => $name)
                    <option value="{{ $id }}" @selected((string) old('department_head_id', $department->department_head_id) === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('department_head_id')
                <p class="{{ $errorClass }}">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('hr.departments.index') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200">إلغاء</a>
        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">حفظ</button>
    </div>
</form>
