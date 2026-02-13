<x-mail::message>
    # {{ $subjectString }}

    {{ $content }}

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>