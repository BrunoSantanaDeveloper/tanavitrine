@component('mail::message')
# {{ __('auth.verification.email.subject') }}

{{ __('auth.verification.email.greeting') }}

{{ __('auth.verification.email.line1') }}

@component('mail::button', ['url' => $verificationUrl])
{{ __('auth.verification.email.action') }}
@endcomponent

{{ __('auth.verification.email.line2') }}

{{ __('auth.verification.email.salutation') }},<br>
{{ __('auth.verification.email.team', ['app' => config('app.name')]) }}
@endcomponent
