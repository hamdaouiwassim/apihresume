@component('mail::message')
# {{ $headline }}

{{ __('Hi :name,', ['name' => $user->name ?: __('there')]) }}

{!! nl2br(e($message)) !!}

@if(count($links) > 0)
{{ __('Try the new features:') }}

@foreach($links as $link)
@component('mail::button', ['url' => $link['url']])
{{ $link['label'] }}
@endcomponent

@endforeach
@endif

{{ __('We would love your feedback — reply to this email if something does not work as expected.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
@endcomponent
