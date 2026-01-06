@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
🏛️ <strong>CivicDash</strong>
@else
🏛️ <strong>{{ $slot }}</strong>
@endif
</a>
</td>
</tr>
<!-- Barre tricolore -->
<tr>
<td style="background: linear-gradient(90deg, #002654 33%, #ffffff 33%, #ffffff 66%, #ed2939 66%); height: 4px; padding: 0;"></td>
</tr>
