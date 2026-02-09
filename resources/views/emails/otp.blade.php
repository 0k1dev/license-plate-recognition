@if(isset($dbContent) && !empty($dbContent))
{!! $dbContent !!}
@else
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin: 0;
        }

        .otp-box {
            background-color: #f9f9f9;
            border: 2px dashed #4CAF50;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }

        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #4CAF50;
            letter-spacing: 8px;
            margin: 15px 0;
        }

        .content {
            line-height: 1.6;
            color: #555;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
            font-size: 12px;
        }

        .warning {
            color: #ff5722;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $user->name }}</strong>,</p>

            <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Vui lòng sử dụng mã OTP bên dưới để hoàn tất quá trình:</p>

            <div class="otp-box">
                <p style="margin: 0; color: #666;">Mã OTP của bạn là:</p>
                <div class="otp-code">{{ $otp }}</div>
                <p style="margin: 0; color: #666; margin-top: 10px;">
                    <small>Mã này sẽ hết hạn sau <strong>{{ $expiresIn }} phút</strong></small>
                </p>
            </div>

            <p class="warning">⚠️ Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này và liên hệ với chúng tôi ngay.</p>

            <p>Trân trọng,<br>
                <strong>Đội ngũ {{ config('app.name') }}</strong>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
        </div>
    </div>
</body>

</html>
@endif