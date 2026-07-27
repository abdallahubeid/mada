@props(['url'])
@php
    $logoUrl = \App\Models\Setting::assetUrl(\App\Models\Setting::getValue('site_logo'));
    $appName = config('app.name', 'Veyra ERP');
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $appName }}" style="max-height: 48px; width: auto;">
@elseif (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
<span style="font-size: 22px; font-weight: 700; color: #1f2937;">{{ $appName }}</span>
@endif
</a>
</td>
</tr>
