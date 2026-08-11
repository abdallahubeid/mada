{{--
    The body is HR-authored free text. `e()` first, then nl2br — escaping after
    nl2br would escape the <br> tags it just produced, and echoing raw would
    let a pasted <script> reach the candidate's mail client.
--}}
<x-mail::message>
{!! nl2br(e($body)) !!}

<br>
{{ config('app.name') }}
</x-mail::message>
