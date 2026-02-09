# 🚀 QUICK START - EMAIL SYSTEM

## 1️⃣ Cấu hình SMTP (5 phút)

### **Bước 0: Chạy Migration (QUAN TRỌNG!)**

```bash
# Migration tables cho email templates đã được tạo sẵn
php artisan migrate
```

**Lưu ý**: Nếu gặp lỗi "Table not found", migrations đã được setup sẵn trong `database/migrations/2026_01_30_*`.

---

### **Option A: Mailtrap (Development - Khuyến nghị)**

1. Đăng ký free tại: https://mailtrap.io
2. Tạo inbox mới
3. Copy credentials và update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=abc123  # từ Mailtrap
MAIL_PASSWORD=xyz789  # từ Mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@appbds.test"
MAIL_FROM_NAME="App BDS"
```

4. Clear config:

```bash
php artisan config:clear
```

---

## 2️⃣ Test Email (2 phút)

### **Test qua Tinker:**

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

$user = User::first();
Mail::to($user->email)->send(new OtpMail($user, '123456', 5));
```

**Kiểm tra**: Vào Mailtrap inbox → Xem email đã nhận

---

## 3️⃣ Sử dụng API (3 phút)

### **Bước 1: Login để lấy token**

```bash
POST http://localhost:8000/api/v1/auth/login
X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password",
  "device_name": "test-device"
}
```

**Response**:

```json
{
  "access_token": "1|xxxxxx...",
  ...
}
```

### **Bước 2: Gửi OTP Email**

```bash
POST http://localhost:8000/api/v1/emails/send-otp
Authorization: Bearer {access_token_từ_bước_1}
X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb
Content-Type: application/json

{
  "email": "test@example.com",
  "otp": "654321",
  "expires_in": 5
}
```

**Success Response**:

```json
{
    "success": true,
    "message": "Email OTP đã được gửi thành công."
}
```

---

## 4️⃣ Quản lý Templates trong Admin

1. Login admin: http://localhost:8000/admin
2. Vào menu **"Email Templates"**
3. Click **"Create"**
4. Chọn template type
5. Customize nội dung
6. Save → Template tự động áp dụng

---

## 📋 API Endpoints Summary

| Endpoint                              | Body                       | Mục đích     |
| ------------------------------------- | -------------------------- | ------------ |
| `POST /emails/send-otp`               | `{email, otp, expires_in}` | Gửi OTP      |
| `POST /emails/property-approved`      | `{property_id}`            | BĐS duyệt    |
| `POST /emails/property-rejected`      | `{property_id, reason}`    | BĐS từ chối  |
| `POST /emails/phone-request-approved` | `{request_id}`             | SĐT duyệt    |
| `POST /emails/custom`                 | `{to, subject, message}`   | Email custom |

---

## 🔧 Troubleshooting Common Issues

### ❌ **Email không gửi được**

**Check:**

1. SMTP config đúng chưa?
2. `php artisan config:clear` chưa?
3. Internet connection?
4. Check logs: `storage/logs/laravel.log`

### ❌ **Response 401 Unauthorized**

**Fix**: Thêm header `Authorization: Bearer {token}`

### ❌ **Response 403 Forbidden**

**Fix**: Thêm header `X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb`

### ❌ **Response 422 Validation Error**

**Fix**: Kiểm tra request body, đảm bảo:

- Email tồn tại trong DB
- OTP đúng 6 số
- property_id/request_id hợp lệ

---

## 💡 Pro Tips

1. **Dùng Mailtrap** cho development - không spam email thật
2. **Queue emails** trong production để tăng performance
3. **Log mọi failures** để debug
4. **Rate limit** email endpoints
5. **Monitor** email delivery rates

---

## 📚 Full Documentation

- **SMTP Config**: `docs/EMAIL_CONFIGURATION.md`
- **API Reference**: `docs/API_EMAIL.md`
- **Full Summary**: `docs/EMAIL_INTEGRATION_SUMMARY.md`

---

✅ **That's it! Email system is ready to use.**

Có vấn đề? Check documentation hoặc contact dev team.
