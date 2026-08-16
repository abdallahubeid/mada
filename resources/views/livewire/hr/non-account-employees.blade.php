<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-ink-900">موظفون بلا حسابات</h1>
            <p class="mt-2 max-w-2xl text-base leading-relaxed text-mist-500">
                موظفون مسجّلون في النظام دون حساب دخول. افتح التفاصيل للاطّلاع على ملف الموظف، أو أنشئ له حساباً وحدّد بريده الإلكتروني قبل الإرسال.
            </p>
        </div>

        <a href="{{ route('hr.employees.index') }}" wire:navigate
           class="inline-flex h-10 items-center gap-2 rounded-lg bg-mist-100 px-4 text-sm font-semibold text-ink-700 transition duration-150 hover:bg-mist-200">
            كل الموظفين
        </a>
    </div>

    <div class="mt-6">
        <label for="employee-search" class="sr-only">بحث</label>
        <input
            id="employee-search"
            type="search"
            wire:model.live.debounce.400ms="search"
            placeholder="ابحث بالاسم أو البريد أو المسمى الوظيفي"
            class="block h-11 w-full max-w-md rounded-lg border border-mist-300 bg-white px-4 text-sm text-ink-900 transition duration-150 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/25"
        >
    </div>

    <div class="mada-surface mt-5 w-full overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-mist-50">
                <tr>
                    <th scope="col" class="w-12 px-4 py-3 text-center text-xs font-semibold tracking-wider text-mist-500">#</th>
                    <th scope="col" class="px-4 py-3 text-start text-xs font-semibold tracking-wider text-mist-500">الموظف</th>
                    <th scope="col" class="px-4 py-3 text-start text-xs font-semibold tracking-wider text-mist-500">القسم</th>
                    <th scope="col" class="px-4 py-3 text-start text-xs font-semibold tracking-wider text-mist-500">البريد الإلكتروني</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold tracking-wider text-mist-500">الإجراءات</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-mist-200">
                @forelse ($rows as $employee)
                    <tr wire:key="employee-{{ $employee->id }}" class="transition duration-150 hover:bg-mist-50">
                        <td class="px-4 py-3 text-center text-sm text-mist-500 tabular">
                            {{ $loop->iteration + ($rows->currentPage() - 1) * $rows->perPage() }}
                        </td>

                        <td class="px-4 py-3 text-start">
                            <span class="font-semibold text-ink-900">{{ $employee->full_name }}</span>
                            @if ($employee->job_title)
                                <span class="mt-0.5 block text-xs text-mist-400">{{ $employee->job_title }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-start text-mist-500">{{ $employee->department?->name ?: '—' }}</td>

                        <td class="px-4 py-3 text-start">
                            @if (filled($employee->email))
                                <x-ui.ltr>{{ $employee->email }}</x-ui.ltr>
                            @else
                                {{-- Not an error: the create-account modal lets HR type one in. --}}
                                <span class="text-xs text-mist-400">يُحدَّد عند الإنشاء</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    wire:click="viewDetails({{ $employee->id }})"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-mist-100 px-3 text-xs font-semibold text-ink-700 transition duration-150 hover:bg-mist-200"
                                >
                                    عرض التفاصيل
                                </button>

                                <button
                                    type="button"
                                    wire:click="startCreate({{ $employee->id }})"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-brand-500 px-3 text-xs font-semibold text-white transition duration-150 hover:bg-brand-600 active:translate-y-px"
                                >
                                    إنشاء حساب
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.table-empty :colspan="5" message="لا يوجد موظفون بلا حسابات — الجميع لديه حساب دخول." />
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <div class="mt-5">{{ $rows->links() }}</div>
    @endif

    {{-- ── Details drawer ────────────────────────────────────────────── --}}
    @if ($viewing)
        <div class="fixed inset-0 z-50 flex" role="dialog" aria-modal="true" aria-labelledby="details-title" wire:key="details-{{ $viewing->id }}">
            <div class="absolute inset-0 bg-ink-950/40" wire:click="closeDetails" aria-hidden="true"></div>

            {{-- `end-0` not `right-0`: the drawer opens from the inline end, so it mirrors correctly in RTL. --}}
            <div class="relative ms-auto flex h-full w-full max-w-md flex-col bg-white shadow-2xl">
                <header class="flex items-start justify-between gap-4 border-b border-mist-200 px-6 py-5">
                    <div>
                        <h2 id="details-title" class="font-display text-xl font-bold text-ink-900">{{ $viewing->full_name }}</h2>
                        <p class="mt-1 text-sm text-mist-500">{{ $viewing->job_title ?: 'بدون مسمى وظيفي' }}</p>
                    </div>
                    <button type="button" wire:click="closeDetails" aria-label="إغلاق"
                            class="rounded-lg p-2 text-mist-500 transition duration-150 hover:bg-mist-100 hover:text-ink-900">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <dl class="space-y-4">
                        @foreach ([
                            ['القسم', $viewing->department?->name],
                            ['البريد الإلكتروني', $viewing->email],
                            ['رقم الجوال', $viewing->phone],
                            ['الهوية الوطنية', $viewing->national_id],
                            ['تاريخ الالتحاق', $viewing->joining_date?->format('Y-m-d')],
                            ['العنوان', $viewing->address],
                        ] as [$label, $value])
                            <div class="flex items-start justify-between gap-4 border-b border-mist-100 pb-3">
                                <dt class="shrink-0 text-sm text-mist-400">{{ $label }}</dt>
                                <dd class="text-end text-sm font-semibold text-ink-900">
                                    @if (filled($value))
                                        <x-ui.ltr>{{ $value }}</x-ui.ltr>
                                    @else
                                        <span class="font-normal text-mist-400">—</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($viewing->cv_path)
                        {{-- Streamed through the authorised route, never a direct file link. --}}
                        <a href="{{ route('hr.employees.cv', $viewing) }}"
                           class="mt-6 inline-flex h-10 items-center gap-2 rounded-lg bg-mist-100 px-4 text-sm font-semibold text-ink-700 transition duration-150 hover:bg-mist-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            تنزيل السيرة الذاتية
                        </a>
                    @endif
                </div>

                <footer class="border-t border-mist-200 px-6 py-4">
                    <button type="button" wire:click="startCreate({{ $viewing->id }})"
                            class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white transition duration-150 hover:bg-brand-600">
                        إنشاء حساب لهذا الموظف
                    </button>
                </footer>
            </div>
        </div>
    @endif

    {{-- ── Create-account modal ──────────────────────────────────────── --}}
    @if ($creating)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="create-title" wire:key="create-{{ $creating->id }}">
            <div class="absolute inset-0 bg-ink-950/40" wire:click="closeCreate" aria-hidden="true"></div>

            <div class="mada-surface relative w-full max-w-lg p-6 sm:p-7">
                <h2 id="create-title" class="font-display text-xl font-bold text-ink-900">إنشاء حساب دخول</h2>
                <p class="mt-1.5 text-sm text-mist-500">سيصل الموظف بريد ترحيبي يحتوي كلمة مرور مؤقتة.</p>

                {{-- Read-only identity, so the operator can see who they are acting on. --}}
                <div class="mt-5 rounded-lg bg-mist-50 p-4 ring-1 ring-ink-900/5">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-mist-400">الموظف</span>
                        <span class="text-sm font-semibold text-ink-900">{{ $creating->full_name }}</span>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between gap-4">
                        <span class="text-sm text-mist-400">القسم</span>
                        <span class="text-sm font-semibold text-ink-900">{{ $creating->department?->name ?: '—' }}</span>
                    </div>
                </div>

                <form wire:submit="createAccount" class="mt-5 space-y-5">
                    <div>
                        <label for="account-email" class="mb-1.5 block text-sm font-medium text-ink-700">البريد الإلكتروني</label>
                        <input
                            id="account-email"
                            type="email"
                            dir="ltr"
                            wire:model="accountEmail"
                            placeholder="name@company.com"
                            class="block h-11 w-full rounded-lg border px-3 text-start text-sm text-ink-900 transition duration-150 placeholder:text-mist-400 focus:outline-none focus:ring-2 @error('accountEmail') border-critical-500 focus:ring-critical-500/25 @else border-mist-300 focus:border-brand-500 focus:ring-brand-500/25 @enderror"
                        >
                        @error('accountEmail')
                            <p class="mt-1.5 text-xs font-medium text-critical-500">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-xs text-mist-400">يُحفظ هذا البريد في ملف الموظف أيضاً.</p>
                        @enderror
                    </div>

                    <div>
                        <label for="account-role" class="mb-1.5 block text-sm font-medium text-ink-700">الدور</label>
                        <select
                            id="account-role"
                            wire:model="accountRole"
                            class="block h-11 w-full rounded-lg border border-mist-300 bg-white px-3 text-sm text-ink-900 transition duration-150 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/25"
                        >
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('accountRole')
                            <p class="mt-1.5 text-xs font-medium text-critical-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" wire:click="closeCreate"
                                class="inline-flex h-11 items-center rounded-lg bg-mist-100 px-5 text-sm font-semibold text-ink-700 transition duration-150 hover:bg-mist-200">
                            إلغاء
                        </button>

                        <button type="submit" wire:loading.attr="disabled" wire:target="createAccount"
                                class="inline-flex h-11 items-center gap-2 rounded-lg bg-brand-500 px-5 text-sm font-semibold text-white transition duration-150 hover:bg-brand-600 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-60">
                            <svg wire:loading wire:target="createAccount" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25" />
                                <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                            </svg>
                            تأكيد إنشاء الحساب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
