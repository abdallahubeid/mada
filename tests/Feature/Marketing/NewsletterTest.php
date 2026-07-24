<?php

use App\Mail\Marketing\NewsletterSubscribed;
use Illuminate\Support\Facades\Mail;

test('a valid newsletter signup sends mail and flashes status', function () {
    Mail::fake();

    $this->from(route('landing'))
        ->post(route('marketing.newsletter.store'), [
            'email' => 'subscriber@example.com',
        ])
        ->assertRedirect(route('landing'))
        ->assertSessionHas('newsletter_status');

    Mail::assertSent(NewsletterSubscribed::class, function (NewsletterSubscribed $mail) {
        return $mail->email === 'subscriber@example.com'
            && $mail->hasTo(config('mail.from.address'));
    });
});

test('newsletter signup requires a valid email', function () {
    Mail::fake();

    $this->from(route('landing'))
        ->post(route('marketing.newsletter.store'), [
            'email' => 'invalid',
        ])
        ->assertRedirect(route('landing'))
        ->assertSessionHasErrors(['email']);

    Mail::assertNothingSent();
});
