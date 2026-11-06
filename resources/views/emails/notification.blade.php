@component('components::mail', ['title' => $notification->title])

@component('components::header', ['url' => config('app.url'), 'color' => '#C6A15B'])
{{ $notification->title }}
@endcomponent

@component('components::body')
<p>Hello {{ $user->name }},</p>

<p>{{ $notification->message }}</p>

@if (!empty($notification->data['action_url']))
    @component('components::button', ['url' => $notification->data['action_url'], 'color' => '#C6A15B'])
    View Details
    @endcomponent
@endif

<p>Best regards,<br>The EventHub Team</p>
@endcomponent

@component('components::footer')
© {{ date('Y') }} EventHub Technologies (Pvt) Ltd. All rights reserved.
@endcomponent

@endcomponent