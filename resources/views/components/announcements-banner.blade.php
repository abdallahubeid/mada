@php
    use App\Domain\Tenancy\Enums\AnnouncementType;
    use App\Domain\Tenancy\Models\Announcement;

    $bannerAnnouncements = Announcement::query()
        ->active()
        ->orderByDesc('is_pinned')
        ->latest('published_at')
        ->limit(5)
        ->get();

    $typeStyles = [
        AnnouncementType::Info->value => 'border-sky-400/40 bg-sky-400/10 text-sky-900 dark:text-sky-100',
        AnnouncementType::Warning->value => 'border-amber-400/40 bg-amber-400/10 text-amber-900 dark:text-amber-100',
        AnnouncementType::Event->value => 'border-brand-500/40 bg-brand-500/10 text-white dark:text-brand-100',
        AnnouncementType::Urgent->value => 'border-danger-solid/40 bg-danger-solid/10 text-danger-solid',
    ];
@endphp

@if ($bannerAnnouncements->isNotEmpty())
    <div class="mb-4 space-y-2" data-testid="announcements-banner">
        @foreach ($bannerAnnouncements as $announcement)
            <div @class([
                'rounded-xl border px-4 py-3 shadow-sm',
                $typeStyles[$announcement->type->value] ?? $typeStyles['info'],
            ])>
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($announcement->is_pinned)
                                <span class="rounded-md bg-ink-900/10 px-2 py-0.5 text-xs font-bold dark:bg-white/10">مثبّت</span>
                            @endif
                            <span class="rounded-md bg-white/50 px-2 py-0.5 text-xs font-semibold dark:bg-ink-950/30">
                                {{ $announcement->type->label() }}
                            </span>
                            <h3 class="font-display text-sm font-medium">{{ $announcement->title }}</h3>
                        </div>
                        <p class="mt-1 text-sm leading-relaxed opacity-90">{{ $announcement->content }}</p>
                    </div>
                    @if ($announcement->expires_at)
                        <p class="shrink-0 text-xs opacity-70" dir="ltr">حتى {{ $announcement->expires_at->format('Y-m-d') }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
