<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;

class EmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'key' => 'otp-email',
                'language' => 'vi',
                'name' => 'Email OTP - Quên Mật Khẩu',
                'view' => 'emails.otp',
                'subject' => 'Mã OTP Đặt Lại Mật Khẩu - {{app_name}}',
                'preheader' => 'Sử dụng mã OTP này để đặt lại mật khẩu của bạn',
                'title' => 'Mã OTP',
                'content' => 'Email này chứa mã OTP để đặt lại mật khẩu tài khoản của bạn.',
            ],
            [
                'key' => 'property-approved',
                'language' => 'vi',
                'name' => 'Thông Báo BĐS Được Duyệt',
                'view' => 'emails.property-approved',
                'subject' => 'BĐS của bạn đã được duyệt - {{app_name}}',
                'preheader' => 'Chúc mừng! Bất động sản của bạn đã được phê duyệt',
                'title' => 'BĐS Được Duyệt',
                'content' => 'Bất động sản của bạn đã được kiểm duyệt và chấp thuận. Bạn có thể bắt đầu tạo bài đăng.',
            ],
            [
                'key' => 'property-rejected',
                'language' => 'vi',
                'name' => 'Thông Báo BĐS Bị Từ Chối',
                'view' => 'emails.property-rejected',
                'subject' => 'BĐS của bạn cần chỉnh sửa - {{app_name}}',
                'preheader' => 'Bất động sản của bạn cần được chỉnh sửa lại',
                'title' => 'BĐS Cần Chỉnh Sửa',
                'content' => 'Bất động sản của bạn chưa đạt yêu cầu. Vui lòng xem lý do và chỉnh sửa lại.',
            ],
            [
                'key' => 'phone-request-approved',
                'language' => 'vi',
                'name' => 'Yêu Cầu Xem SĐT Được Duyệt',
                'view' => 'emails.phone-request-approved',
                'subject' => 'Yêu cầu xem SĐT đã được duyệt - {{app_name}}',
                'preheader' => 'Bạn đã được cấp quyền xem số điện thoại chủ nhà',
                'title' => 'Xem SĐT Chủ Nhà',
                'content' => 'Yêu cầu xem số điện thoại chủ nhà của bạn đã được phê duyệt.',
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                [
                    'key' => $data['key'],
                    'language' => $data['language'],
                ],
                $data
            );
        }

        $this->command->info('✓ Email templates seeded successfully!');
        $this->command->info('  → 4 templates created/updated');
        $this->command->info('  → Check Admin Panel → Email Templates');
    }
}
