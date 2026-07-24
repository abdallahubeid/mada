@props([
    'status' => null,
    'label' => null,
])

@php
    /*
     * Consistent, palette-locked status colouring for the whole Platform
     * Console (docs/DESIGN_SYSTEM.md §4 `status-badge`). Covers the 5-state
     * tenant lifecycle (ARCHITECTURE.md §3) plus the support-thread and
     * admin-account states reused across pages 10 & 11. Every entry ships a
     * light + `dark:` pass (ADR-15).
     */
    $map = [
        // Tenant lifecycle
        'pending_verification' => ['label' => 'بانتظار التحقق', 'text' => 'text-sky-600 dark:text-sky-400', 'bg' => 'bg-sky-500/10', 'dot' => 'bg-sky-500'],
        'pending_approval' => ['label' => 'بانتظار الموافقة', 'text' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-500/10', 'dot' => 'bg-amber-500'],
        'active' => ['label' => 'نشط', 'text' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-500/10', 'dot' => 'bg-emerald-400'],
        'suspended' => ['label' => 'موقوف', 'text' => 'text-danger-solid', 'bg' => 'bg-danger-solid/10', 'dot' => 'bg-danger-solid'],
        'cancelled' => ['label' => 'ملغى', 'text' => 'text-mist-500 dark:text-mist-400', 'bg' => 'bg-mist-400/10', 'dot' => 'bg-mist-400'],
    ];

    $cfg = $map[$status] ?? ['label' => $label ?? $status, 'text' => 'text-mist-500 dark:text-mist-400', 'bg' => 'bg-mist-400/10', 'dot' => 'bg-mist-400'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium', $cfg['text'], $cfg['bg']]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $cfg['dot'] }}"></span>
    {{ $label ?? $cfg['label'] }}
</span>
