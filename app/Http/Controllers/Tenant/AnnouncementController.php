<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Enums\AnnouncementType;
use App\Domain\Tenancy\Models\Announcement;
use App\Events\Tenancy\UrgentAnnouncementPublished;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreAnnouncementRequest;
use App\Http\Requests\Tenant\UpdateAnnouncementRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AnnouncementController extends Controller
{
    public function __construct(private readonly TrashManager $trash) {}

    public function index(): View
    {
        $announcements = Announcement::query()
            ->with('creator')
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->latest('id')
            ->paginate(config('app.paginate_page'));

        return view('tenant.announcements.index', [
            'announcements' => $announcements,
            'types' => AnnouncementType::cases(),
            'canManage' => auth()->user()?->can('tenant.announcements.manage') ?? false,
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $announcement = Announcement::query()->create([
            ...$data,
            'type' => $data['type'],
            'published_at' => $data['published_at'] ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
            'created_by' => $request->user()?->id,
        ]);

        if ($announcement->type === AnnouncementType::Urgent) {
            event(new UrgentAnnouncementPublished($announcement));
        }

        flash()->success('تم نشر التعميم بنجاح.');

        return redirect()->route('tenant.announcements.index');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validated();

        $announcement->update([
            ...$data,
            'published_at' => $data['published_at'] ?? $announcement->published_at ?? now(),
            'expires_at' => $data['expires_at'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        if ($announcement->fresh()->type === AnnouncementType::Urgent) {
            event(new UrgentAnnouncementPublished($announcement->fresh()));
        }

        flash()->info('تم تحديث التعميم بنجاح.');

        return redirect()->route('tenant.announcements.index');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        abort_unless(auth()->user()?->can('tenant.announcements.manage') ?? false, 403);

        $announcement->delete();

        $this->trash->flashSoftDeleted('تم حذف التعميم بنجاح.', 'announcements', $announcement);

        return redirect()->route('tenant.announcements.index');
    }
}
