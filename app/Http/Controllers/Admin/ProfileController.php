<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\User;
use App\Services\Admin\PlatformNotificationPublisher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use League\Flysystem\FilesystemException;
use RuntimeException;

/**
 * Authenticated user profile — personal details, avatar, and password.
 */
class ProfileController extends Controller
{
    public function __construct(private PlatformNotificationPublisher $notifications) {}

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load('avatar');

        return view('admin.profile.index', [
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();
        $passwordChanged = ! empty($validated['password']);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($passwordChanged) {
            $user->password = $validated['password'];
        }

        $user->save();

        $avatarStored = $this->syncAvatar($request, $user);

        if ($passwordChanged) {
            $this->notifications->passwordChanged($user);
        }

        /*
         * The outcome has to be decided HERE, not inside syncAvatar(). A
         * warning flashed in there was immediately overwritten by this success
         * message, so a failed upload still reported "تم التحديث بنجاح" and the
         * user had no way to know the avatar had not been saved.
         */
        if ($avatarStored) {
            flash()->info('تم تحديث الملف الشخصي بنجاح.');
        } else {
            flash()->warning('تم حفظ بياناتك، لكن تعذّر رفع الصورة الشخصية. الصورة السابقة لم تتغيّر.');
        }

        return redirect()->route('admin.profile');
    }

    /**
     * @return bool true when the avatar was stored, or when none was supplied
     */
    private function syncAvatar(UpdateProfileRequest $request, User $user): bool
    {
        /** @var UploadedFile|null $file */
        $file = $request->file('avatar');

        if ($file === null) {
            return true;
        }

        /*
         * STORE FIRST, DELETE SECOND. The original order deleted the existing
         * avatar before writing the replacement, so a storage failure cost the
         * user the avatar they already had on top of returning a 500 — the one
         * outcome worse than the upload simply not working.
         *
         * The catch is not defensive padding. `$file->store()` throws
         * League\Flysystem\UnableToCreateDirectory when the target directory
         * cannot be created, and the disk's `'throw' => false` does NOT cover
         * it — that setting only swallows UnableToWriteFile. So a permission
         * problem, a full disk, or a read-only mount arrives here as an
         * unhandled exception and the whole profile update 500s, discarding
         * the name and password changes that already saved.
         *
         * FilesystemException is the common parent of the Flysystem failures;
         * RuntimeException covers the local driver's own move() errors.
         */
        try {
            $path = $file->store('user/avatar', 'custom');
        } catch (FilesystemException|RuntimeException $e) {
            // Reported, not swallowed: the operator needs this in the log, even
            // though the request itself now completes successfully.
            report($e);

            return false;
        }

        // `store()` can also return false rather than throw, depending on which
        // layer fails. Treated identically: no path means no image row, since
        // `images.path` is NOT NULL and a blank one renders as a broken image.
        if ($path === false || $path === '') {
            return false;
        }

        $user->avatar()->get()->each->forceDelete();

        $user->images()->create([
            'collection' => 'avatar',
            'disk' => 'custom',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'alt_text' => $user->name,
            'sort_order' => 0,
        ]);

        return true;
    }
}
