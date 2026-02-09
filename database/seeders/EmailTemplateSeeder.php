<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'otp',
                'name' => 'Gửi mã OTP xác thực',
                'subject' => 'Mã xác thực OTP của bạn - {{ config("app.name") }}',
                'content' => '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;">
    <h2 style="color: #2563eb; text-align: center;">Xác thực tài khoản</h2>
    <p>Xin chào <strong>{{ $userName }}</strong>,</p>
    <p>Bạn vừa yêu cầu đăng nhập/xác thực tài khoản tại hệ thống BDS.</p>
    <p>Mã OTP của bạn là:</p>
    <div style="text-align: center; margin: 30px 0;">
        <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #1e40af; background: #f3f4f6; padding: 15px 30px; border-radius: 8px;">{{ $otp }}</span>
    </div>
    <p>Mã này sẽ hết hạn trong vòng <strong>{{ $expiresIn }} phút</strong>.</p>
    <p style="color: #6b7280; font-size: 14px;">Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
    <p style="text-align: center; color: #9ca3af; font-size: 12px;">Email này được gửi tự động, vui lòng không trả lời.</p>
</div>',
                'view' => 'emails.otp',
            ],
            [
                'key' => 'property-approved',
                'name' => 'Thông báo BĐS được duyệt',
                'subject' => 'Tin đăng "{{ $property->title }}" của bạn đã được duyệt',
                'content' => '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;">
    <h2 style="color: #16a34a; text-align: center;">Tin đăng đã được duyệt!</h2>
    <p>Xin chào <strong>{{ $userName }}</strong>,</p>
    <p>Chúc mừng! Tin đăng BĐS của bạn đã được kiểm duyệt và công khai trên hệ thống.</p>
    <div style="background: #f0fdf4; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
        <h3 style="margin: 0 0 10px 0; font-size: 16px;">{{ $property->title }}</h3>
        <p style="margin: 0; color: #4b5563;">Địa chỉ: {{ $property->address }}</p>
    </div>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route("filament.admin.resources.properties.view", $property->id) }}" style="background: #16a34a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Xem tin đăng</a>
    </div>
    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
    <p style="text-align: center; color: #9ca3af; font-size: 12px;">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.</p>
</div>',
                'view' => 'emails.property-approved',
            ],
            [
                'key' => 'property-rejected',
                'name' => 'Thông báo BĐS bị từ chối',
                'subject' => 'Tin đăng "{{ $property->title }}" bị từ chối',
                'content' => '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;">
    <h2 style="color: #dc2626; text-align: center;">Tin đăng bị từ chối</h2>
    <p>Xin chào <strong>{{ $userName }}</strong>,</p>
    <p>Rất tiếc, tin đăng BĐS của bạn chưa đạt yêu cầu kiểm duyệt của chúng tôi.</p>
    <div style="background: #fef2f2; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
        <h3 style="margin: 0 0 10px 0; font-size: 16px;">{{ $property->title }}</h3>
        <p><strong>Lý do từ chối:</strong></p>
        <p style="color: #b91c1c; font-style: italic;">{{ $reason }}</p>
    </div>
    <p>Vui lòng chỉnh sửa lại tin đăng theo hướng dẫn và gửi lại yêu cầu.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route("filament.admin.resources.properties.edit", $property->id) }}" style="background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Sửa tin đăng</a>
    </div>
    <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
    <p style="text-align: center; color: #9ca3af; font-size: 12px;">Nếu bạn có thắc mắc, vui lòng liên hệ bộ phận hỗ trợ.</p>
</div>',
                'view' => 'emails.property-rejected',
            ],
        ];

        foreach ($templates as $tpl) {
            DB::table('vb_email_templates')->updateOrInsert(
                ['key' => $tpl['key']],
                array_merge($tpl, [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'language' => 'vi',
                ])
            );
        }
    }
}
