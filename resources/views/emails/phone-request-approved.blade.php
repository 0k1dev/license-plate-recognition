<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu Cầu Xem SĐT Được Duyệt</title>
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
            border-bottom: 2px solid #2196F3;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin: 0;
        }

        .success-badge {
            background-color: #2196F3;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin: 20px 0;
        }

        .phone-box {
            background-color: #e3f2fd;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .phone-number {
            font-size: 28px;
            font-weight: bold;
            color: #1976D2;
            letter-spacing: 2px;
        }

        .property-info {
            background-color: #f9f9f9;
            border-left: 4px solid #2196F3;
            padding: 20px;
            margin: 20px 0;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>

        <div class="content">
            <div style="text-align: center;">
                <div class="success-badge">✓ ĐÃ ĐƯỢC DUYỆT</div>
            </div>

            <p>Xin chào <strong>{{ $user->name }}</strong>,</p>

            <p>Yêu cầu xem số điện thoại chủ nhà của bạn đã được <strong>CHẤP THUẬN</strong>. Bạn có thể liên hệ trực tiếp với chủ nhà theo thông tin dưới đây:</p>

            <div class="property-info">
                <h3 style="margin-top: 0; color: #2196F3;">Thông tin BĐS</h3>
                <p><strong>Tên BĐS:</strong> {{ $property->title }}</p>
                <p><strong>Địa chỉ:</strong> {{ $property->address }}</p>
            </div>

            <div class="phone-box">
                <p style="margin: 0; color: #666;">📞 Số điện thoại chủ nhà:</p>
                <div class="phone-number">{{ $property->owner_phone }}</div>
            </div>

            <p><strong>Lưu ý quan trọng:</strong></p>
            <ul>
                <li>Vui lòng giữ thông tin này bảo mật</li>
                <li>Chỉ sử dụng cho mục đích liên hệ nghiệp vụ</li>
                <li>Tôn trọng thời gian và quyền riêng tư của chủ nhà</li>
            </ul>

            <p>Chúc bạn thành công trong việc trao đổi với chủ nhà!</p>

            <p>Trân trọng,<br>
                <strong>Đội ngũ {{ config('app.name') }}</strong>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>