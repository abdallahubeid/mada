<?php

namespace App\Http\Requests\Auth;

use App\Http\Controllers\Auth\LoginController;
use App\Services\Admin\PlatformNotificationPublisher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * Validates and performs authentication for the unified login page
 * (docs/USER_JOURNEYS.md). Encapsulating the {@see Auth::attempt()} call
 * here — rather than in the controller — keeps
 * {@see LoginController} focused purely on
 * post-authentication redirect logic.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors, in Arabic to match the
     * fully Arabic login UI.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->recordFailedLoginAttempt();

            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة، يرجى المحاولة مرة أخرى.',
            ]);
        }

        cache()->forget($this->failedLoginCacheKey());
    }

    private function recordFailedLoginAttempt(): void
    {
        $key = $this->failedLoginCacheKey();
        $attempts = (int) cache()->get($key, 0) + 1;
        cache()->put($key, $attempts, now()->addMinutes(10));

        if ($attempts < 5) {
            return;
        }

        // Fire once per streak window (reset after alert).
        cache()->forget($key);

        app(PlatformNotificationPublisher::class)->securityAlert(
            'محاولات دخول فاشلة متكررة',
            'رُصدت '.$attempts.' محاولات دخول فاشلة على «'.$this->string('email').'» خلال فترة قصيرة.',
            Route::has('admin.audit-log') ? route('admin.audit-log') : null,
        );
    }

    private function failedLoginCacheKey(): string
    {
        return 'auth.failed-login.'.sha1(strtolower((string) $this->input('email')).'|'.$this->ip());
    }
}
