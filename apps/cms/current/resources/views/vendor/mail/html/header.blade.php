{{-- Header of the notification e-mails (password reset, portal invitation, operational alerts): the site logo when a raster one is usable, else the app name as before. The text part (vendor/mail/text) is the framework's and unchanged. Author: Ald1n <https://ald1n.com> --}}
@props(['url'])
@php($mailLogo = \App\Support\MailBranding::logo())
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($mailLogo)
<img src="{{ $mailLogo['src'] }}" alt="{{ $mailLogo['alt'] }}" height="{{ $mailLogo['height'] }}"@if($mailLogo['width']) width="{{ $mailLogo['width'] }}"@endif style="display:block;border:0;height:{{ $mailLogo['height'] }}px;width:{{ $mailLogo['width'] ? $mailLogo['width'].'px' : 'auto' }}">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
