<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;

class SystemEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key'      => 'otp-email',
                'name'     => 'OTP Xác Thực',
                'language' => 'vi',
                'view'     => 'emails.otp',
                'subject'  => 'Mã OTP xác thực tài khoản của bạn',
                'content'  => '
<p>Xin chào <strong>{{ $user->name ?? $userName ?? "Khách" }}</strong>,</p>
<p>Bạn vừa yêu cầu mã xác thực để truy cập hệ thống. Vui lòng sử dụng mã OTP dưới đây:</p>

<div style="text-align: center; margin: 36px 0;">
    <div style="display: inline-block; background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 48px;">
        <span style="font-family: monospace; font-size: 40px; font-weight: 800; color: #4f46e5; letter-spacing: 8px; line-height: 1;">{{ $otp }}</span>
    </div>
    <p style="margin-top: 14px; color: #64748b; font-size: 14px;">Mã có hiệu lực trong <strong>{{ $expiresIn }} phút</strong>.</p>
</div>

<p style="color: #94a3b8; font-size: 14px; border-top: 1px solid #f1f5f9; padding-top: 20px; margin-top: 20px;">
    ⚠️ Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email này.
</p>',
            ],
            [
                'key'      => 'property-approved',
                'name'     => 'BĐS Được Duyệt',
                'language' => 'vi',
                'view'     => 'emails.property-approved',
                'subject'  => 'Tin đăng "{{ $property->title ?? "" }}" đã được duyệt ✓',
                'content'  => '
<p>Xin chào <strong>{{ $user->name ?? $userName ?? "Khách" }}</strong>,</p>
<p>🎉 Chúc mừng! Tin đăng bất động sản của bạn đã được kiểm duyệt thành công và hiện đang được hiển thị trên hệ thống.</p>

<div style="background-color: #f0fdf4; border-left: 4px solid #10b981; border-radius: 8px; padding: 20px 24px; margin: 28px 0;">
    <p style="margin: 0 0 6px 0; font-weight: 700; font-size: 17px; color: #065f46;">{{ $property->title ?? "" }}</p>
    <p style="margin: 0; color: #166534; font-size: 14px;">Địa chỉ: {{ $property->address ?? "" }}</p>
</div>

<p>Bạn có thể đăng nhập vào ứng dụng để tiếp cận khách hàng tiềm năng ngay hôm nay.</p>',
            ],
            [
                'key'      => 'property-rejected',
                'name'     => 'BĐS Bị Từ Chối',
                'language' => 'vi',
                'view'     => 'emails.property-rejected',
                'subject'  => 'Tin đăng "{{ $property->title ?? "" }}" cần chỉnh sửa',
                'content'  => '
<p>Xin chào <strong>{{ $user->name ?? $userName ?? "Khách" }}</strong>,</p>
<p>Rất tiếc, tin đăng bất động sản <strong>"{{ $property->title ?? "" }}"</strong> của bạn chưa đáp ứng các tiêu chuẩn đăng tin của chúng tôi.</p>

<div style="background-color: #fef2f2; border-left: 4px solid #ef4444; border-radius: 8px; padding: 20px 24px; margin: 28px 0;">
    <p style="margin: 0 0 8px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: #991b1b;">Lý do từ chối:</p>
    <p style="margin: 0; color: #b91c1c; font-style: italic; font-size: 15px;">"{{ $reason ?? "" }}"</p>
</div>

<p>Vui lòng cập nhật lại thông tin theo hướng dẫn và gửi lại để chúng tôi xem xét duyệt sớm nhất.</p>',
            ],
            [
                'key'      => 'phone-request-approved',
                'name'     => 'Yêu Cầu Xem SĐT Được Duyệt',
                'language' => 'vi',
                'view'     => 'emails.phone-request-approved',
                'subject'  => 'Thông tin liên hệ chủ nhà đã sẵn sàng',
                'content'  => '
<p>Xin chào <strong>{{ $user->name ?? $userName ?? "Khách" }}</strong>,</p>
<p>Yêu cầu xem số điện thoại chính chủ cho bất động sản <strong>"{{ $property->title ?? $propertyTitle ?? "" }}"</strong> của bạn đã được phê duyệt.</p>

<div style="background-color: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 12px; padding: 24px; margin: 28px 0;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 10px 0; color: #6b7280; font-size: 14px; font-weight: 500;">Chủ sở hữu:</td>
            <td style="padding: 10px 0; color: #1e1b4b; font-size: 16px; font-weight: 700; text-align: right;">{{ $ownerName ?? $property->contact_name ?? "" }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #6b7280; font-size: 14px; font-weight: 500;">Số điện thoại:</td>
            <td style="padding: 12px 0; border-top: 1px solid #e2e8f0; color: #4f46e5; font-size: 24px; font-weight: 800; text-align: right; letter-spacing: 1px;">{{ $ownerPhone ?? $property->owner_phone ?? "" }}</td>
        </tr>
    </table>
</div>

<p style="color: #64748b; font-size: 14px; font-style: italic;">Vui lòng bảo mật thông tin này và chỉ sử dụng cho mục đích giao dịch bất động sản.</p>',
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                ['key' => $data['key']],
                $data
            );
        }

        $this->command?->info('✓ Email templates seeded successfully!');
    }
}
