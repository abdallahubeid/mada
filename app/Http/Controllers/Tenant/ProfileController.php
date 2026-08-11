<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

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

        $this->syncAvatar($request, $user);

        flash()->info('تم تحديث الملف الشخصي بنجاح.');

        return redirect()->route('profile.edit');
    }

    private function syncAvatar(UpdateProfileRequest $request, User $user): void
    {
        /** @var UploadedFile|null $file */
        $file = $request->file('avatar');

        if ($file === null) {
            return;
        }

        $user->avatar()->get()->each->forceDelete();

        $path = $file->store('user/avatar', 'custom');

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
    }
}
