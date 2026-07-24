@php
    // Stubbed navigation tree mirroring docs/VEYRA_DOCS.md §9 / USER_JOURNEYS.md.
    // Entries with `route => null` haven't been built yet in Phase 1 and render
    // as disabled "Soon" items instead of dead links.
    $navGroups = [
        [
            'label' => 'My Space',
            'roles' => null,
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard'],
                ['label' => 'My Tasks', 'route' => null],
                ['label' => 'My Attendance', 'route' => null],
                ['label' => 'My Time Off', 'route' => null],
                ['label' => 'My Payslips', 'route' => null],
            ],
        ],
        [
            'label' => 'HR',
            'roles' => ['Owner', 'HR Manager'],
            'items' => [
                ['label' => 'Employees & Contracts', 'route' => null],
                ['label' => 'Attendance Hub', 'route' => null],
                ['label' => 'Time Off — Action Center', 'route' => null],
                ['label' => 'Recruitment', 'route' => null],
            ],
        ],
        [
            'label' => 'Projects',
            'roles' => ['Owner', 'Project Manager'],
            'items' => [
                ['label' => 'Strategic Hierarchy', 'route' => null],
                ['label' => 'Boards', 'route' => null],
                ['label' => 'Timesheets', 'route' => null],
            ],
        ],
        [
            'label' => 'Finance',
            'roles' => ['Owner', 'Finance Manager'],
            'items' => [
                ['label' => 'Payroll', 'route' => null],
                ['label' => 'Invoicing & Expenses', 'route' => null],
                ['label' => 'Clients', 'route' => null],
                ['label' => 'Financial Dashboard', 'route' => null],
            ],
        ],
        [
            'label' => 'Settings',
            'roles' => ['Owner'],
            'items' => [
                ['label' => 'Company Profile', 'route' => null],
                ['label' => 'Roles & Permissions', 'route' => null],
                ['label' => 'Departments & Leave Policies', 'route' => null],
                ['label' => 'Subscription & Billing', 'route' => null],
            ],
        ],
    ];
@endphp

{{-- Mobile scrim --}}
<div
    x-show="sidebarOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-ink-950/60 lg:hidden"
></div>

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 start-0 z-40 w-64 shrink-0 -translate-x-full border-e border-mist-200 bg-white transition-transform duration-300 ease-out lg:static dark:border-ink-600 dark:bg-ink-800"
>
    <div class="flex h-16 items-center gap-2 border-b border-mist-200 px-6 dark:border-ink-600">
        <span class="font-display text-lg font-bold text-emerald-600 dark:text-emerald-400">Veyra</span>
        <span class="text-sm text-mist-500">ERP</span>
    </div>

    <nav class="space-y-6 overflow-y-auto px-3 py-6">
        @foreach ($navGroups as $group)
            @if (empty($group['roles']) || auth()->user()?->hasAnyRole($group['roles']))
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-mist-400">
                        {{ $group['label'] }}
                    </p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($group['items'] as $item)
                            <li>
                                @if ($item['route'] && Route::has($item['route']))
                                    @php $isActive = request()->routeIs($item['route']); @endphp
                                    <a
                                        href="{{ route($item['route']) }}"
                                        wire:navigate
                                        class="flex items-center border-s-2 px-3 py-2 text-sm font-medium transition-all duration-300 ease-out active:scale-[0.98]
                                            {{ $isActive
                                                ? 'border-emerald-400 bg-emerald-400/10 text-emerald-600 underline decoration-emerald-400 decoration-2 underline-offset-4 dark:text-emerald-400'
                                                : 'border-transparent text-ink-600 underline-offset-2 hover:border-mist-300 hover:bg-mist-100 dark:text-mist-300 dark:hover:border-ink-500 dark:hover:bg-ink-700' }}"
                                    >
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <span class="flex items-center justify-between gap-2 border-s-2 border-transparent px-3 py-2 text-sm text-mist-400 dark:text-mist-600">
                                        {{ $item['label'] }}
                                        <span class="rounded-full bg-mist-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-mist-500 dark:bg-ink-700 dark:text-mist-400">
                                            Soon
                                        </span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>
</aside>
