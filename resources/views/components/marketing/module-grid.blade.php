@props([
    'modules' => null,
])

@php
    $modules ??= collect();
@endphp

{{--
    Laid out like the template's short-intro block: the whole section sits
    on a peach panel that grows in on scroll, with dashed waves and circles,
    and the content is centred over it. The modules are white cards so they
    stay legible on the tinted ground.
--}}
<section id="modules" class="pb-16">
    <div class="site-container-xl">
        <div class="site-panel-host px-4 py-[70px]">
            <x-marketing.panel circles="section" />

            <x-marketing.section-heading
                :eyebrow="$settings['modules_badge_text'] ?? 'الوحدات'"
                :title="$settings['modules_title'] ?? ''"
                :subtitle="$settings['modules_sub_title'] ?? ''"
            />

            <div class="mx-auto mt-10 grid max-w-6xl gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modules as $module)
                    <article class="site-card flex items-start gap-4 p-6 text-start">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-site-peach bg-site-cream text-site-primary">
                            @if ($module->icon)
                                <x-ui.icon :name="$module->icon" class="h-6 w-6" />
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="site-h5">{{ $module->title }}</h3>
                            <p class="mt-2">{{ $module->description }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
