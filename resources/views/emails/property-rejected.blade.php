@php
$iconHtml = view('emails.components.icon', ['symbol' => '!', 'bg' => '#fee2e2', 'color' => '#dc2626', 'size' => 36])->render();
@endphp

@if(isset($dbContent) && !empty($dbContent))
@component('emails.components.layout', [
'title' => 'Tin đăng cần chỉnh sửa',
'preheader' => 'Tin đăng "' . ($property->title ?? '') . '" cần chỉnh sửa trước khi hiển thị.',
'accentColor' => '#dc2626',
'emailTitle' => 'Tin đăng cần chỉnh sửa',
'iconHtml' => $iconHtml,
])
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;color:#374151;line-height:1.7;">
    {!! $dbContent !!}
</div>
@endcomponent
@else
@component('emails.components.layout', [
'title' => 'Tin đăng cần chỉnh sửa',
'preheader' => 'Tin đăng "' . ($property->title ?? '') . '" cần chỉnh sửa. Vui lòng cập nhật và gửi lại.',
'accentColor' => '#dc2626',
'emailTitle' => 'Thông tin cần chỉnh sửa',
'emailSubtitle' => 'Vui lòng cập nhật theo hướng dẫn bên dưới',
'iconHtml' => $iconHtml,
])
<p style="margin:0 0 16px;">
    Xin chào <strong>{{ $user->name ?? $userName ?? 'Khách' }}</strong>,
</p>

<p style="margin:0 0 24px;color:#6b7280;">
    Tin đăng <strong style="color:#374151;">"{{ $property->title ?? '' }}"</strong> chưa đáp ứng tiêu chuẩn đăng tin.
</p>

{{-- Reason Card --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin:0 0 24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="padding-bottom:10px;">
                        <span style="display:inline-block;background-color:#fee2e2;border-radius:4px;padding:2px 10px;font-size:11px;font-weight:600;color:#991b1b;text-transform:uppercase;letter-spacing:0.5px;">Từ chối</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom:4px;font-size:12px;font-weight:600;color:#991b1b;text-transform:uppercase;letter-spacing:0.5px;">
                        Lý do
                    </td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#b91c1c;font-style:italic;line-height:1.6;">
                        "{{ $reason ?? 'Vui lòng liên hệ admin để biết thêm chi tiết.' }}"
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Property Info --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f9fafb;border-radius:8px;margin:0 0 24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#374151;">Thông tin tin đăng</p>
            <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">
                <strong style="color:#374151;">Tiêu đề:</strong> {{ $property->title ?? '' }}
            </p>
            @if(!empty($property->address))
            <p style="margin:0;font-size:13px;color:#6b7280;">
                <strong style="color:#374151;">Địa chỉ:</strong> {{ $property->address }}
            </p>
            @endif
        </td>
    </tr>
</table>

{{-- Hướng dẫn --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fffbeb;border:1px solid #fde68a;border-radius:8px;margin:0 0 24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#92400e;">Hướng dẫn chỉnh sửa</p>
            <p style="margin:0 0 4px;font-size:13px;color:#78350f;">1. Đăng nhập vào ứng dụng</p>
            <p style="margin:0 0 4px;font-size:13px;color:#78350f;">2. Chỉnh sửa thông tin theo lý do từ chối</p>
            <p style="margin:0;font-size:13px;color:#78350f;">3. Gửi lại để được xem xét sớm nhất</p>
        </td>
    </tr>
</table>

<p style="margin:0;color:#6b7280;font-size:14px;">
    Trân trọng,<br>
    <strong style="color:#374151;">Đội ngũ {{ config('app.name') }}</strong>
</p>
@endcomponent
@endif