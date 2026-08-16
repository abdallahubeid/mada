@props([
    'status' => null,
    'label' => null,
])

@php
    /*
     * Consistent, palette-locked status colouring for the whole Platform
     * Console (docs/DESIGN_SYSTEM.md §4 `status-badge`). Covers the 6-state
     * tenant lifecycle (ARCHITECTURE.md §3) plus the support-thread and
     * admin-account states reused across pages 10 & 11. Every entry ships a
     * light + `dark:` pass (ADR-15).
     */
    $map = [
        // Tenant lifecycle
        'pending_verification' => ['label' => 'بانتظار التحقق', 'text' => 'text-sky-600 dark:text-sky-400', 'bg' => 'bg-sky-500/10', 'dot' => 'bg-sky-500'],
        'pending_approval' => ['label' => 'بانتظار الموافقة', 'text' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-500/10', 'dot' => 'bg-amber-500'],
        'active' => ['label' => 'نشط', 'text' => 'text-brand-600 dark:text-brand-300', 'bg' => 'bg-brand-500/10', 'dot' => 'bg-brand-500'],
        /*
         * Added with the sixth state (ADR-04 amended 2026-08-09). Without it a
         * rejected tenant fell through to the default branch and rendered the
         * raw English enum value as its label, in an otherwise Arabic console.
         *
         * Rose rather than `danger-solid`: refusal is terminal and needs to
         * read differently at a glance from suspension, which is reversible.
         */
        'rejected' => ['label' => 'مرفوض', 'text' => 'text-rose-600 dark:text-rose-400', 'bg' => 'bg-rose-500/10', 'dot' => 'bg-rose-500'],
        'suspended' => ['label' => 'موقوف', 'text' => 'text-danger-solid', 'bg' => 'bg-danger-solid/10', 'dot' => 'bg-danger-solid'],
        'cancelled' => ['label' => 'ملغى', 'text' => 'text-mist-500 dark:text-mist-400', 'bg' => 'bg-mist-400/10', 'dot' => 'bg-mist-400'],
    ];

    $cfg = $map[$status] ?? ['label' => $label ?? $status, 'text' => 'text-mist-500 dark:text-mist-400', 'bg' => 'bg-mist-400/10', 'dot' => 'bg-mist-400'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium', $cfg['text'], $cfg['bg']]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $cfg['dot'] }}"></span>
    {{ $label ?? $cfg['label'] }}
</span>
