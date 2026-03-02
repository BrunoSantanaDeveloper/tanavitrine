@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Tanavitrine' || trim($slot) === 'Tanavitrine')
<img src="{{ asset('tanavitrine_light_icon1.png') }}" class="logo" alt="Tanavitrine Logo" style="max-height: 50px; max-width: 200px;">
@elseif (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
