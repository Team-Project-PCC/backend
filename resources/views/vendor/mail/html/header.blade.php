@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://maulidaa.my.id/storage/image/logo.png" class="logo" alt="Museum Gita Rupa">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
