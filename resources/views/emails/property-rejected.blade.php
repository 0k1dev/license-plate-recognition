<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BĐS Bị Từ Chối</title>
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
            border-bottom: 2px solid #ff5722;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin: 0;
        }

        .reject-badge {
            background-color: #ff5722;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin: 20px 0;
        }

        .property-info {
            background-color: #f9f9f9;
            border-left: 4px solid #ff5722;
            padding: 20px;
            margin: 20px 0;
        }

        .reason-box {
            background-color: #fff3e0;
            border: 1px solid #ff9800;
            border-radius: 5px;
            padding: 15px;
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
                <div class="reject-badge">✗ BỊ TỪ CHỐI</div>
            </div>

            <p>Xin chào <strong>{{ $user->name }}</strong>,</p>

            <p>Rất tiếc, bất động sản của bạn đã được xem xét nhưng <strong>CHƯA ĐẠT YÊU CẦU</strong>. Vui lòng xem lý do từ chối bên dưới và chỉnh sửa để gửi lại.</p>

            <div class="property-info">
                <h3 style="margin-top: 0; color: #ff5722;">Thông tin BĐS</h3>
                <p><strong>Tên BĐS:</strong> {{ $property->title }}</p>
                <p><strong>Địa chỉ:</strong> {{ $property->address }}</p>
            </div>

            <div class="reason-box">
                <h4 style="margin-top: 0; color: #ff6f00;">📝 Lý do từ chối:</h4>
                <p>{{ $reason }}</p>
            </div>

            <p>Bạn có thể chỉnh sửa thông tin bất động sản và gửi lại để được xét duyệt.</p>

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