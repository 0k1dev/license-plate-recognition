<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;

class FixEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo template fake để tránh lỗi query khi login/gửi mail
        if (!EmailTemplate::where('key', 'otp-email')->exists()) {
            EmailTemplate::create([
                'name' => 'OTP Login/Reset',
                'key' => 'otp-email',
                'subject' => 'Mã xác thực OTP: {{otp}}',
                'content' => '<p>Xin chào {{userName}},</p><p>Mã OTP của bạn là: <strong>{{otp}}</strong></p>',
                'view' => 'emails.otp',
                'language' => 'vi',
            ]);
        }
    }
}
