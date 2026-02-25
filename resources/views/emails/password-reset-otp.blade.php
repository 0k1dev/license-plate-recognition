@php
$iconHtml = view('emails.components.icon', ['symbol' => '↻', 'bg' => '#fee2e2', 'color' => '#dc2626', 'size' => 36])->render();
@endphp

@component('emails.components.layout', [
'title' => 'Đặt lại mật khẩu',
'preheader' => 'Mã OTP đặt lại mật khẩu: ' . $otp . ' — Hiệu lực ' . $ttl . ' phút',
'accentColor' => '#dc2626',
'emailTitle' => 'Đặt lại mật khẩu',
'emailSubtitle' => 'Sử dụng mã bên dưới để tạo mật khẩu mới',
'iconHtml' => $iconHtml,
])
<p style="margin:0 0 16px;">
    Xin chào <strong>{{ $name }}</strong>,
</p>

<p style="margin:0 0 20px;color:#6b7280;">
    Bạn vừa yêu cầu đặt lại mật khẩu. Vui lòng nhập mã OTP sau:
</p>

{{-- OTP Box --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;">
                <tr>
                    <td style="padding:20px 44px;text-align:center;">
                        <span style="font-family:'Courier New',Courier,monospace;font-size:36px;font-weight:700;color:#dc2626;letter-spacing:10px;line-height:1;">{{ $otp }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p style="margin:0 0 24px;text-align:center;font-size:13px;color:#9ca3af;">
    Mã có hiệu lực trong <strong style="color:#dc2626;">{{ $ttl }} phút</strong>
</p>

{{-- Lưu ý --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #f3f4f6;padding-top:20px;">
    <tr>
        <td style="font-size:13px;color:#9ca3af;line-height:1.7;">
            <p style="margin:0 0 4px;"><strong style="color:#6b7280;">Lưu ý bảo mật</strong></p>
            <p style="margin:0 0 2px;">— Không chia sẻ mã OTP với bất kỳ ai, kể cả nhân viên hỗ trợ.</p>
            <p style="margin:0 0 2px;">— Sử dụng mật khẩu mạnh sau khi đặt lại.</p>
            <p style="margin:0;">— Nếu bạn không yêu cầu, hãy bỏ qua — tài khoản vẫn an toàn.</p>
        </td>
    </tr>
</table>
@endcomponent