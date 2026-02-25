@php
$iconHtml = view('emails.components.icon', ['symbol' => '●', 'bg' => '#ede9fe', 'color' => '#7c3aed', 'size' => 36])->render();
@endphp

@if(isset($dbContent) && !empty($dbContent))
@component('emails.components.layout', [
'title' => 'Mã xác thực OTP',
'preheader' => 'Mã OTP: ' . ($otp ?? '******') . ' — Hiệu lực ' . ($expiresIn ?? 5) . ' phút',
'accentColor' => '#7c3aed',
'emailTitle' => 'Mã xác thực OTP',
'iconHtml' => $iconHtml,
])
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;color:#374151;line-height:1.7;">
    {!! $dbContent !!}
</div>
@endcomponent
@else
@component('emails.components.layout', [
'title' => 'Mã xác thực OTP',
'preheader' => 'Mã OTP: ' . ($otp ?? '******') . ' — Hiệu lực ' . ($expiresIn ?? 5) . ' phút',
'accentColor' => '#7c3aed',
'emailTitle' => 'Mã xác thực tài khoản',
'emailSubtitle' => 'Sử dụng mã bên dưới để hoàn tất xác thực',
'iconHtml' => $iconHtml,
])
<p style="margin:0 0 16px;">
    Xin chào <strong>{{ $user->name ?? $userName ?? 'Khách' }}</strong>,
</p>

<p style="margin:0 0 20px;color:#6b7280;">
    Bạn vừa yêu cầu xác thực tài khoản. Vui lòng nhập mã OTP sau:
</p>

{{-- OTP Box --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f3ff;border:1px solid #e9e5f5;border-radius:8px;">
                <tr>
                    <td style="padding:20px 44px;text-align:center;">
                        <span style="font-family:'Courier New',Courier,monospace;font-size:36px;font-weight:700;color:#7c3aed;letter-spacing:10px;line-height:1;">{{ $otp ?? '------' }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p style="margin:0 0 24px;text-align:center;font-size:13px;color:#9ca3af;">
    Mã có hiệu lực trong <strong style="color:#7c3aed;">{{ $expiresIn ?? 5 }} phút</strong>
</p>

{{-- Lưu ý --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #f3f4f6;padding-top:20px;">
    <tr>
        <td style="font-size:13px;color:#9ca3af;line-height:1.7;">
            <p style="margin:0 0 4px;"><strong style="color:#6b7280;">Lưu ý bảo mật</strong></p>
            <p style="margin:0 0 2px;">— Không chia sẻ mã OTP với bất kỳ ai.</p>
            <p style="margin:0 0 2px;">— {{ config('app.name') }} không bao giờ hỏi mã OTP qua điện thoại.</p>
            <p style="margin:0;">— Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>
        </td>
    </tr>
</table>
@endcomponent
@endif