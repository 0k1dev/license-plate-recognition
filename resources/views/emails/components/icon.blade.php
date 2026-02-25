{{--
    Icon component cho email templates.
    Tạo icon hình tròn bằng table (compatible mọi email client).

    Usage: @include('emails.components.icon', ['symbol' => '✓', 'bg' => '#dcfce7', 'color' => '#16a34a', 'size' => 36])
--}}
@php
$size = $size ?? 36;
$fontSize = $fontSize ?? (int)($size * 0.45);
$bg = $bg ?? '#dbeafe';
$color = $color ?? '#2563eb';
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td width="{{ $size }}" height="{{ $size }}" align="center" valign="middle" style="width:{{ $size }}px;height:{{ $size }}px;background-color:{{ $bg }};border-radius:{{ (int)($size / 2) }}px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:{{ $fontSize }}px;font-weight:700;color:{{ $color }};line-height:{{ $size }}px;text-align:center;">
            {{ $symbol ?? '●' }}
        </td>
    </tr>
</table>