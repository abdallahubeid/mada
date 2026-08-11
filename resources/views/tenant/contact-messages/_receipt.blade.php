@php
    /** @var string $status pending|delivered|read */
    $color = match ($status) {
        'read' => 'text-sky-500',
        default => ($onDark ?? false) ? 'text-emerald-900/70' : 'text-mist-400',
    };
@endphp

@if ($status === 'pending')
    <span class="inline-flex {{ $color }}" title="تم الحفظ" aria-label="تم الحفظ">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
    </span>
@else
    <span class="inline-flex {{ $color }}" title="{{ $status === 'read' ? 'تمت القراءة' : 'تم التسليم' }}" aria-label="{{ $status === 'read' ? 'تمت القراءة' : 'تم التسليم' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        <svg xmlns="http://www.w3.org/2000/svg" class="-ms-2 h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
    </span>
@endif
