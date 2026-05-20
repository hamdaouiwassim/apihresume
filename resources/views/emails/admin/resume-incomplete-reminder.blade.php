@component('mail::message')
# {{ __('Your resume is almost ready') }}

{{ __('Hi :name,', ['name' => $user->name ?: __('there')]) }}

{{ __('You started building **:resume** on :app. A few sections still need attention — add your experience and summary so recruiters see your best profile.', ['resume' => $resume->name, 'app' => config('app.name')]) }}

@component('mail::button', ['url' => $editUrl])
{{ __('Continue my resume') }}
@endcomponent

{{ __('If you have questions, reply to this email or contact our support team.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
@endcomponent
