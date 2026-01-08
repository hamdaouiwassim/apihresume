@component('mail::message')
# You've been invited to collaborate!

{{ $owner->name }} has invited you to edit their resume: **{{ $resume->name }}**

You can now make changes to this resume and help improve it.

@component('mail::button', ['url' => $acceptUrl])
Accept Invitation & Start Editing
@endcomponent

If you don't have an account yet, you'll need to [sign up]({{ config('app.frontend_url', env('FRONTEND_URL')) }}/register) first, then click the button above.

If you didn't expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent










