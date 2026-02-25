@php
$iconHtml = view('emails.components.icon', ['symbol' => '☎', 'bg' => '#dbeafe', 'color' => '#2563eb', 'size' => 36])->render();
@endphp

@if(isset($dbContent) && !empty($dbContent))
@component('emails.components.layout', [
'title' => 'Thông tin liên hệ chủ nhà',
'preheader' => 'Yêu cầu xem SĐT cho "' . ($property->title ?? $propertyTitle ?? '') . '" đã được duyệt.',
'accentColor' => '#2563eb',
'emailTitle' => 'Thông tin liên hệ chủ nhà',
'iconHtml' => $iconHtml,
])
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;color:#374151;line-height:1.7;">
    {!! $dbContent !!}
</div>
@endcomponent
@else
@component('emails.components.layout', [
'title' => 'Thông tin liên hệ chủ nhà',
'preheader' => 'Yêu cầu xem SĐT cho "' . ($property->title ?? $propertyTitle ?? '') . '" đã được phê duyệt.',
'accentColor' => '#2563eb',
'emailTitle' => 'Yêu cầu đã được duyệt',
'emailSubtitle' => 'Thông tin liên hệ chủ nhà có bên dưới',
'iconHtml' => $iconHtml,
])
<p style="margin:0 0 16px;">
    Xin chào <strong>{{ $user->name ?? $userName ?? 'Khách' }}</strong>,
</p>

<p style="margin:0 0 24px;color:#6b7280;">
    Yêu cầu xem số điện thoại chính chủ cho BĐS <strong style="color:#374151;">"{{ $property->title ?? $propertyTitle ?? '' }}"</strong> đã được phê duyệt.
</p>

{{-- Phone Info Card --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;margin:0 0 24px;">
    <tr>
        <td style="padding:24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                {{-- Owner --}}
                <tr>
                    <td style="padding-bottom:4px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                        Chủ sở hữu
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom:16px;font-size:16px;font-weight:600;color:#111827;">
                        {{ $ownerName ?? $property->contact_name ?? 'N/A' }}
                    </td>
                </tr>
                {{-- Divider --}}
                <tr>
                    <td style="padding-bottom:16px;border-top:1px dashed #bfdbfe;font-size:0;line-height:0;">&nbsp;</td>
                </tr>
                {{-- Phone --}}
                <tr>
                    <td style="padding-bottom:4px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                        Số điện thoại
                    </td>
                </tr>
                <tr>
                    <td>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="background-color:#dbeafe;border-radius:6px;padding:10px 24px;">
                                    <span style="font-family:'Courier New',Courier,monospace;font-size:22px;font-weight:700;color:#2563eb;letter-spacing:2px;">{{ $ownerPhone ?? $property->owner_phone ?? 'N/A' }}</span>
                                </td>
                            </tr>
                        </table>
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
            <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#374151;">BĐS liên quan</p>
            <p style="margin:0 0 4px;font-size:13px;color:#374151;font-weight:500;">{{ $property->title ?? $propertyTitle ?? '' }}</p>
            @if(!empty($property->address))
            <p style="margin:0;font-size:13px;color:#6b7280;">{{ $property->address }}</p>
            @endif
        </td>
    </tr>
</table>

{{-- Bảo mật --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #f3f4f6;padding-top:20px;">
    <tr>
        <td style="font-size:13px;color:#9ca3af;line-height:1.7;">
            <p style="margin:0 0 4px;"><strong style="color:#6b7280;">Lưu ý bảo mật</strong></p>
            <p style="margin:0 0 2px;">— Chỉ sử dụng thông tin này cho mục đích giao dịch BĐS.</p>
            <p style="margin:0;">— Không chia sẻ SĐT chủ nhà cho bên thứ ba khi chưa được phép.</p>
        </td>
    </tr>
</table>

<p style="margin:24px 0 0;color:#6b7280;font-size:14px;">
    Trân trọng,<br>
    <strong style="color:#374151;">Đội ngũ {{ config('app.name') }}</strong>
</p>
@endcomponent
@endif