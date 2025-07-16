{{-- Simple @if --}}
@if(true)
    Simple
@endif

{{-- @if with variable --}}
@if($user)
    Variable
@endif

{{-- @if with method call --}}
@if($user->isActive())
    Method call
@endif

{{-- @if with logical operators --}}
@if($user && $user->isActive())
    Logical AND
@endif

{{-- @if with complex expression --}}
@if($user->isActive() && $user->hasPermission('admin'))
    Complex expression
@endif