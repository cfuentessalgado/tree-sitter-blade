{{-- Test all conditional directives --}}

@if($user->isActive() && $user->hasPermission('admin'))
    <p>If works</p>
@endif

@unless($user->isGuest() || $user->isBanned())
    <p>Unless works</p>
@endunless

@isset($user->profile->avatar)
    <p>Isset works</p>
@endisset

@empty($user->notifications->unread())
    <p>Empty works</p>
@endempty