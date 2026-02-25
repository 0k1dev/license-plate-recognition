@php
$iconHtml = view('emails.components.icon', ['symbol' => '✓', 'bg' => '#dcfce7', 'color' => '#16a34a', 'size' => 36])->render();
@endphp

@if(isset($dbContent) && !empty($dbContent))
@component('emails.components.layout', [
'title' => 'Tin đăng đã được duyệt',
'preheader' => 'Tin đăng "' . ($property->title ?? '') . '" đã được duyệt thành công.',
'accentColor' => '#16a34a',
'emailTitle' => 'Tin đăng đã được duyệt',
'iconHtml' => $iconHtml,
])
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;color:#374151;line-height:1.7;">
    {!! $dbContent !!}
</div>
@endcomponent
@else
@component('emails.components.layout', [
'title' => 'Tin đăng đã được duyệt',
'preheader' => 'Tin đăng "' . ($property->title ?? '') . '" đã được duyệt và đang hiển thị.',
'accentColor' => '#16a34a',
'emailTitle' => 'Tin đăng đã được duyệt',
'emailSubtitle' => 'BĐS của bạn đang hiển thị công khai trên hệ thống',
'iconHtml' => $iconHtml,
])
<p style="margin:0 0 16px;">
    Xin chào <strong>{{ $user->name ?? $userName ?? 'Khách' }}</strong>,
</p>

<p style="margin:0 0 24px;color:#6b7280;">
    Tin đăng bất động sản của bạn đã được kiểm duyệt và hiện đang hiển thị công khai.
</p>

{{-- Property Card --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin:0 0 24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="padding-bottom:10px;">
                        <span style="display:inline-block;background-color:#dcfce7;border-radius:4px;padding:2px 10px;font-size:11px;font-weight:600;color:#166534;text-transform:uppercase;letter-spacing:0.5px;">Đã duyệt</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom:6px;font-size:16px;font-weight:600;color:#111827;">
                        {{ $property->title ?? '' }}
                    </td>
                </tr>
                @if(!empty($property->address))
                <tr>
                    <td style="font-size:13px;color:#6b7280;">
                        {{ $property->address }}
                    </td>
                </tr>
                @endif
                @if(!empty($propertyPrice) && $propertyPrice !== 'N/A')
                <tr>
                    <td style="padding-top:8px;font-size:15px;font-weight:600;color:#16a34a;">
                        {{ $propertyPrice }} VNĐ
                    </td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- Bước tiếp theo --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f9fafb;border-radius:8px;margin:0 0 24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#374151;">Bước tiếp theo</p>
            <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">1. Đăng nhập ứng dụng để theo dõi tin đăng</p>
            <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">2. Theo dõi lượt xem và liên hệ từ khách hàng</p>
            <p style="margin:0;font-size:13px;color:#6b7280;">3. Cập nhật thông tin để tối ưu hiệu quả</p>
        </td>
    </tr>
</table>

<p style="margin:0;color:#6b7280;font-size:14px;">
    Trân trọng,<br>
    <strong style="color:#374151;">Đội ngũ {{ config('app.name') }}</strong>
</p>
@endcomponent
@endif