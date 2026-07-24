<?php

use App\Mail\Marketing\ContactInquiry;
use Illuminate\Support\Facades\Mail;

test('the contact page renders the form', function () {
    $this->get(route('marketing.contact'))
        ->assertOk()
        ->assertSee('تواصل معنا')
        ->assertSee('إرسال الرسالة')
        ->assertSee('طلب عرض توضيحي');
});

test('a valid contact submission sends mail and redirects with status', function () {
    Mail::fake();

    $this->post(route('marketing.contact.store'), [
        'name' => 'نورة القحطاني',
        'email' => 'noura@example.com',
        'company' => 'مؤسسة نماء',
        'subject' => 'demo',
        'message' => 'أرغب في حجز عرض توضيحي للمنصّة لفريقنا.',
    ])
        ->assertRedirect(route('marketing.contact'))
        ->assertSessionHas('status');

    Mail::assertSent(ContactInquiry::class, function (ContactInquiry $mail) {
        return $mail->inquiry['email'] === 'noura@example.com'
            && $mail->hasTo(config('mail.from.address'));
    });
});

test('contact form validation rejects incomplete submissions', function () {
    Mail::fake();

    $this->from(route('marketing.contact'))
        ->post(route('marketing.contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => 'demo',
            'message' => 'قصير',
        ])
        ->assertRedirect(route('marketing.contact'))
        ->assertSessionHasErrors(['name', 'email', 'message']);

    Mail::assertNothingSent();
});
