@component('mail::message')
# {{ __('Confirm your email address') }}

{{ __('Hi :name,', ['name' => $user->name ?: __('there')]) }}

{{ __('Please verify your email to unlock downloads, Pro features, and full access to :app.', ['app' => config('app.name')]) }}

@component('mail::button', ['url' => $verificationUrl])
{{ __('Verify email address') }}
@endcomponent

{{ __('If the button does not work, open your account and request a new link:') }}

@component('mail::button', ['url' => $resendUrl])
{{ __('Go to verification page') }}
@endcomponent

@if($showDeletionNotice ?? false)
@component('mail::panel')
**{{ __('Account removal notice') }}**

{{ __('If you do not verify your email within :days days of signing up, your account and all saved data (resumes, cover letters, etc.) may be permanently deleted.', ['days' => $deletionDays ?? 30]) }}

@if(!empty($deletionDeadline))
{{ __('Please verify before :date to keep your account.', ['date' => $deletionDeadline->timezone(config('app.timezone'))->format('F j, Y')]) }}
@endif
@endcomponent
@endif

{{ __('This verification link expires for security.') }}

@if(!($showDeletionNotice ?? false))
{{ __('If you did not create an account, you can ignore this message.') }}
@endif

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
@endcomponent
