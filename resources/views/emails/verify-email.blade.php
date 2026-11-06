@component('components::mail', ['title' => 'Verify Your Email Address'])

@component('components::header', ['url' => $verificationUrl, 'color' => '#C6A15B'])
EventHub Email Verification
@endcomponent

@component('components::body')
<p>Hello {{ $user->name }},</p>

<p>Welcome to EventHub! Please verify your email address by clicking the button below:</p>

{{-- Button --}}
@component('components::button', ['url' => $verificationUrl, 'color' => '#C6A15B'])
Verify Email Address
@endcomponent

<p>If you did not create an account, no further action is required.</p>

<p>Best regards,<br>The EventHub Team</p>
@endcomponent

@component('components::footer')
© {{ date('Y') }} EventHub Technologies (Pvt) Ltd. All rights reserved.
@endcomponent

@endcomponent