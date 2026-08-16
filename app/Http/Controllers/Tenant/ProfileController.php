<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use League\Flysystem\FilesystemException;
use RuntimeException;

/**
 * Tenant authenticated user profile — personal details, avatar crop upload, password.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load('avatar');

        return view('tenant.profile.index', [
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

        /*
         * Decided here rather than inside syncAvatar(): a warning flashed in
         * there would be overwritten by this message, and a failed upload would
         * still report success.
         */
        if ($avatarStored) {
            flash()->info('تم تحديث الملف الشخصي بنجاح.');
        } else {
            flash()->warning('تم حفظ بياناتك، لكن تعذّر رفع الصورة الشخصية. الصورة السابقة لم تتغيّر.');
        }

        return redirect()->route('profile.edit');
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
         * Same ordering and failure handling as the admin profile controller —
         * see App\Http\Controllers\Admin\ProfileController::syncAvatar().
         *
         * Store first so a storage failure cannot destroy the avatar the user
         * already had, and catch it so the whole profile update does not 500
         * and discard the changes that already saved. `'throw' => false` on the
         * disk does NOT cover UnableToCreateDirectory.
         */
        try {
            $path = $file->store('user/avatar', 'custom');
        } catch (FilesystemException|RuntimeException $e) {
            report($e);

            return false;
        }

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
