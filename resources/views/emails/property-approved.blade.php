<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BĐS Đã Được Duyệt</title>
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

        .success-badge {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin: 20px 0;
        }

        .property-info {
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
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

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
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

            <p>Chúc mừng! Bất động sản của bạn đã được kiểm duyệt và <strong>CHẤP THUẬN</strong>. Bạn có thể bắt đầu tạo bài đăng ngay bây giờ.</p>

            <div class="property-info">
                <h3 style="margin-top: 0; color: #4CAF50;">Thông tin BĐS</h3>
                <p><strong>Tên BĐS:</strong> {{ $property->title }}</p>
                <p><strong>Địa chỉ:</strong> {{ $property->address }}</p>
                <p><strong>Loại:</strong> {{ $property->category->name ?? 'N/A' }}</p>
                <p><strong>Khu vực:</strong> {{ $property->area->name ?? 'N/A' }}</p>
                <p><strong>Giá:</strong> {{ number_format($property->price) }} VNĐ</p>
            </div>

            <p>Bạn có thể đăng nhập vào ứng dụng để tạo bài đăng cho bất động sản này.</p>

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