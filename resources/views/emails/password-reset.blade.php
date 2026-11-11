@component('components::mail', ['title' => 'Reset Your Password'])

@component('components::header', ['url' => $resetUrl, 'color' => '#C6A15B'])
Riwaj Grand Password Reset
@endcomponent

@component('components::body')
<p>Hello {{ $user->name }},</p>

<p>You requested a password reset for your Riwaj Grand account. Click the button below to set a new password:</p>

{{-- Button --}}
@component('components::button', ['url' => $resetUrl, 'color' => '#C6A15B'])
Reset Password
@endcomponent

<p>This password reset link will expire in 60 minutes.</p>

<p>If you did not request a password reset, please ignore this email or contact support.</p>

<p>Best regards,<br>The Riwaj Grand Team</p>
@endcomponent

@component('components::footer')
© {{ date('Y') }} Riwaj Grand Technologies (Pvt) Ltd. All rights reserved. • Riwayat bhi, Jiddat bhi
@endcomponent

@endcomponent