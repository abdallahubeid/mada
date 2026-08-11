<?php

namespace App\Mail\Tenant;

use App\Domain\Tenancy\Support\InterviewMessageTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The interview invitation, as composed by the HR Manager.
 *
 * Takes the subject and body ALREADY RENDERED. Substitution happens once, in
 * {@see InterviewMessageTemplate}, before this class
 * is constructed — so the preview endpoint and this mail cannot disagree about
 * what `{interview_date}` resolves to.
 *
 * The body is free text typed by a user. The view escapes it and converts
 * newlines; it is never rendered as raw markup.
 *
 * CC is deliberately NOT a property here. `Mailable` already declares its own
 * `$cc` (a list of `['address' => …, 'name' => …]` entries that `hasCc()` and
 * recipient building read), and a promoted `public array $cc` would shadow it
 * with a flat list of strings. Callers attach copies the ordinary way —
 * `Mail::to($x)->cc($list)->send(...)`.
 */
class CandidateInterviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant.candidate-interview',
        );
    }
}
