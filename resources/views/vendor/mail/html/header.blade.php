@props(['url'])
@php
    $logoUrl = \App\Models\Setting::assetUrl(\App\Models\Setting::getValue('site_logo'));
    $appName = config('app.name', 'Veyra ERP');
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{--
    The uploaded site logo when one is set, otherwise the Veyra wordmark.

    The Laravel-logo branch that sat between these two was removed on
    2026-08-10: it fired whenever the header slot happened to read "Laravel"
    and pulled a remote image from laravel.com into a Veyra customer's inbox.
--}}
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $appName }}">
@else
<span style="font-size: 22px; font-weight: 700; color: #081425; letter-spacing: -0.01em;">{{ $appName }}</span>
@endif
</a>
</td>
</tr>
