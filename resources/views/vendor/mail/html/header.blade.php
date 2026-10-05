@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" class="brand">
<img src="{{ asset('img/simac-logo.jpg') }}" class="logo" width="64" height="56" alt="">
<span class="brand-name">{{ $slot }}</span>
<span class="brand-tagline">{{ __('Raffles') }}</span>
</a>
</td>
</tr>
