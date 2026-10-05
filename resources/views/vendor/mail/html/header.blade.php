@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" class="brand">
<img src="{{ asset('apple-touch-icon.png') }}" class="logo" width="48" height="48" alt="">
<span class="brand-name">{{ $slot }}</span>
<span class="brand-tagline">{{ __('Raffles') }}</span>
</a>
</td>
</tr>
