@component('mail::message')
# Message from {{ $admin->name }}

{!! nl2br(e($bodyMessage)) !!}

---

This message was sent to you by the admin team at {{ config('app.name') }}.

@component('mail::button', ['url' => config('app.url')])
Visit {{ config('app.name') }}
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent

