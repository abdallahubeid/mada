@props([
    'groups',
    'assigned' => [],
    'name' => 'permissions[]',
])

@php
    $assigned = collect($assigned)->flip();
@endphp

<div class="grid gap-4 lg:grid-cols-2">
    @foreach ($groups as $domain => $group)
        <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h3 class="mb-3 font-display text-sm font-medium text-ink-900 dark:text-ink-50">{{ $group['label'] }}</h3>
            <div class="space-y-2">
                @foreach ($group['permissions'] as $permission => $label)
                    <x-admin.permission-toggle
                        :name="$name"
                        :value="$permission"
                        :label="$label"
                        :checked="$assigned->has($permission)"
                    />
                @endforeach
            </div>
        </section>
    @endforeach
</div>
