<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Visualbuilder\EmailTemplates\Models\EmailTemplate;

class SystemEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'otp-email',
                'name' => 'OTP Verification',
                'subject' => 'Mã OTP xác thực của bạn là {{otp}}',
                'content' => '
                    <div style="font-family: Helvetica, Arial, sans-serif; min-width:1000px; overflow:auto; line-height:2">
                      <div style="margin:50px auto; width:70%; padding:20px 0">
                        <div style="border-bottom:1px solid #eee">
                          <a href="" style="font-size:1.4em; color: #00466a; text-decoration:none; font-weight:600">Bất Động Sản</a>
                        </div>
                        <p style="font-size:1.1em">Xin chào {{userName}},</p>
                        <p>Sử dụng mã OTP sau để hoàn tất quá trình xác thực. Mã có hiệu lực trong {{expiresIn}} phút.</p>
                        <h2 style="background: #00466a; margin: 0 auto; width: max-content; padding: 0 10px; color: #fff; border-radius: 4px;">{{otp}}</h2>
                        <p style="font-size:0.9em;">Trân trọng,<br />The BĐS Team</p>
                        <hr style="border:none;border-top:1px solid #eee" />
                      </div>
                    </div>
                ',
                'view' => 'emails.otp',
                'language' => 'vi',
            ],
            [
                'key' => 'property-approved',
                'name' => 'Property Approved',
                'subject' => 'Tin đăng "{{propertyTitle}}" đã được duyệt',
                'content' => '
                    <p>Xin chào {{userName}},</p>
                    <p>Chúc mừng! Tin đăng <strong>{{propertyTitle}}</strong> của bạn đã được duyệt thành công.</p>
                    <p>Bạn có thể xem tin đăng tại đây: <a href="#">Link tin đăng</a></p>
                ',
                'view' => 'emails.property-approved',
                'language' => 'vi',
            ],
            [
                'key' => 'property-rejected',
                'name' => 'Property Rejected',
                'subject' => 'Tin đăng "{{propertyTitle}}" bị từ chối',
                'content' => '
                    <p>Xin chào {{userName}},</p>
                    <p>Rất tiếc, tin đăng <strong>{{propertyTitle}}</strong> của bạn đã bị từ chối.</p>
                    <p><strong>Lý do:</strong> {{reason}}</p>
                    <p>Vui lòng chỉnh sửa và gửi lại.</p>
                ',
                'view' => 'emails.property-rejected',
                'language' => 'vi',
            ],
            [
                'key' => 'phone-request-approved',
                'name' => 'Phone Request Approved',
                'subject' => 'Yêu cầu xem SĐT cho "{{propertyTitle}}" đã được duyệt',
                'content' => '
                    <p>Xin chào {{userName}},</p>
                    <p>Yêu cầu xem số điện thoại chủ nhà của bạn đã được duyệt.</p>
                    <p><strong>Thông tin liên hệ:</strong></p>
                    <ul>
                        <li>Chủ nhà: {{ownerName}}</li>
                        <li>SĐT: <strong>{{ownerPhone}}</strong></li>
                    </ul>
                ',
                'view' => 'emails.phone-request-approved',
                'language' => 'vi',
            ]
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                ['key' => $data['key']],
                $data
            );
        }
    }
}
