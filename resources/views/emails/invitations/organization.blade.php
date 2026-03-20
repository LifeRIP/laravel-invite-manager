<x-mail::message>
    # You are invited to join {{ $organizationName }}

    {{ $inviterName }} has invited you with the role **{{ $role }}**.

    Use this token to accept the invitation from the API:

    **{{ $token }}**

    This invitation expires on {{ $expiresAt->toDayDateTimeString() }}.

    <x-mail::button :url="config('app.url')">
        Open App URL
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
