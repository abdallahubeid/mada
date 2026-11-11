{{--
    Favicon set for every layout: the brand mark (white "م" on #fe6019).
    A favicon uploaded in the admin settings replaces the tab icon; the home
    screen icons and manifest stay on the brand set. `?v=2` busts browser
    caches still holding the retired Veyra "V" icon.
--}}
@php($faviconVersion = '2')
@if (! empty($settings['site_favicon'] ?? null))
    <link rel="icon" href="{{ \App\Models\Setting::assetUrl($settings['site_favicon']) }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}?v={{ $faviconVersion }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32x32.png') }}?v={{ $faviconVersion }}" type="image/png" sizes="32x32">
@endif
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ $faviconVersion }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}?v={{ $faviconVersion }}">
<meta name="theme-color" content="#f8f6f2">
