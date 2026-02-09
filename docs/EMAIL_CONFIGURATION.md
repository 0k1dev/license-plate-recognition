# 📧 HỆ THỐNG EMAIL - HƯỚNG DẪN CẤU HÌNH

## 🎯 Mục Đích

Tài liệu này hướng dẫn cấu hình SMTP để gửi email thật thay vì chỉ log.

## 📋 Các Service Email Phổ Biến

### 1. Gmail SMTP (Miễn phí - Khuyến nghị cho dev/test)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # Lấy từ Google App Password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Cách tạo App Password:**

1. Vào https://myaccount.google.com/security
2. Bật Two-Factor Authentication
3. Tìm "App passwords"
4. Tạo password mới cho "Mail"
5. Copy password 16 ký tự

---

### 2. Mailtrap (Miễn phí - Tốt nhất cho testing)

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@appbds.test"
MAIL_FROM_NAME="${APP_NAME}"
```

**Cách lấy credentials:**

1. Đăng ký tại https://mailtrap.io
2. Tạo inbox mới
3. Copy SMTP credentials

---

### 3. SendGrid (Production - 100 emails/day miễn phí)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Cách setup:**

1. Đăng ký tại https://sendgrid.com
2. Tạo API Key
3. Verify sender email

---

### 4. Mailgun (Production - 5,000 emails/month miễn phí)

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yourdomain.com
MAILGUN_SECRET=your-mailgun-api-key
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

### 5. Amazon SES (Production - Rẻ nhất cho volume lớn)

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## ⚡ Testing Email Locally

Để test không cần SMTP thật:

```env
MAIL_MAILER=log
```

Email sẽ được ghi vào `storage/logs/laravel.log`

---

## 🚀 Khuyến Nghị

| Môi trường           | Service          | Lý do                                   |
| -------------------- | ---------------- | --------------------------------------- |
| **Development**      | Mailtrap         | Không gửi email thật, UI đẹp để test    |
| **Staging**          | Gmail SMTP       | Miễn phí, dễ setup                      |
| **Production**       | SendGrid/Mailgun | Reliable, analytics, deliverability tốt |
| **Production (Lớn)** | Amazon SES       | Rẻ nhất với volume lớn                  |

---

## 📝 Sau Khi Cấu Hình

1. **Update file `.env`** với thông tin SMTP
2. **Clear config cache:**
    ```bash
    php artisan config:clear
    ```
3. **Test gửi email:**
    ```bash
    php artisan tinker
    Mail::raw('Test email', function($msg) {
        $msg->to('your-email@example.com')->subject('Test');
    });
    ```

---

## 🔒 Bảo Mật

- ❌ **ĐỪNG** commit file `.env` lên git
- ✅ **NÊN** dùng environment variables trên server
- ✅ **NÊN** dùng App Passwords thay vì password thật
- ✅ **NÊN** enable rate limiting cho email endpoints

---

## 📧 Danh Sách Email Được Gửi

Hệ thống hiện hỗ trợ các loại email:

1. ✅ **OTP Email** - Quên mật khẩu
2. ✅ **Property Approved** - Thông báo BĐS được duyệt
3. ✅ **Property Rejected** - Thông báo BĐS bị từ chối
4. ✅ **Phone Request Approved** - Thông báo xem SĐT được duyệt
5. ✅ **Custom Email** - Email tùy chỉnh

---

Tạo: 2026-01-30
