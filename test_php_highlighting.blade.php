{{-- Test file for PHP syntax highlighting --}}

{{-- @if directive --}}
@if($user->isActive() && $user->hasPermission('admin'))
    <p>Admin user is active</p>
@endif

{{-- @php @endphp block --}}
@php
    $items = collect([1, 2, 3]);
    $filtered = $items->filter(function($item) {
        return $item > 1;
    });
    $result = $filtered->map(fn($x) => $x * 2);
@endphp

{{-- @foreach directive --}}
@foreach($users as $user)
    <p>{{ $user->name }}</p>
@endforeach

{{-- @class directive (should work now) --}}
<div @class([
    'active' => $user->isActive(),
    'admin' => $user->hasRole('admin'),
    'text-red-500' => $user->hasErrors(),
])>
    Content
</div>

{{-- Regular PHP expressions --}}
<p>{{ $user->name }}</p>
<p>{!! $user->bio !!}</p>