# 🐛 EMAIL SYSTEM TROUBLESHOOTING

## Lỗi: Table 'vb_email_templates' doesn't exist

### **Nguyên nhân**

Plugin Email Templates cần database tables nhưng chưa được migrate.

### **Giải pháp** ✅

```bash
# Migrations đã được setup sẵn, chỉ cần chạy:
php artisan migrate
```

**Expected output:**

```
INFO  Running migrations.

2026_01_30_103001_create_email_templates_themes_table .......... DONE
2026_01_30_103002_create_email_templates_table ................ DONE
```

### **Verify**

```bash
# Kiểm tra tables đã được tạo:
php artisan db:table vb_email_templates
php artisan db:table vb_email_templates_themes
```

---

## Lỗi: SMTP Connection Refused

### **Nguyên nhân**

SMTP server không chạy hoặc config sai.

### **Giải pháp**

1. **Check .env config:**

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username  # ← Kiểm tra
MAIL_PASSWORD=your-password  # ← Kiểm tra
```

2. **Clear config cache:**

```bash
php artisan config:clear
```

3. **Test với log driver trước:**

```env
MAIL_MAILER=log  # Email sẽ ghi vào storage/logs/laravel.log
```

---

## Lỗi: Response 401 Unauthorized

### **Nguyên nhân**

Thiếu hoặc sai Bearer token.

### **Giải pháp**

```bash
# 1. Login để lấy token
POST http://localhost:8000/api/v1/auth/login
X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb
{
  "email": "admin@example.com",
  "password": "password",
  "device_name": "test"
}

# 2. Copy access_token từ response
# 3. Dùng trong header:
Authorization: Bearer {access_token}
```

---

## Lỗi: Response 403 Forbidden

### **Nguyên nhân**

Thiếu API Key header.

### **Giải pháp**

```bash
# Thêm header:
X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb
```

---

## Lỗi: Response 422 Validation Error

### **Ví dụ lỗi:**

```json
{
    "success": false,
    "errors": {
        "email": ["Email không tồn tại trong hệ thống."]
    }
}
```

### **Giải pháp**

Kiểm tra request body:

**Gửi OTP:**

```json
{
    "email": "user@example.com", // ← Phải tồn tại trong DB
    "otp": "123456", // ← Đúng 6 số
    "expires_in": 5 // ← Optional, integer
}
```

**Property Approved:**

```json
{
    "property_id": 1 // ← Property ID phải tồn tại
}
```

**Property Rejected:**

```json
{
    "property_id": 1,
    "reason": "Lý do..." // ← Bắt buộc, max 500 chars
}
```

---

## Email không được gửi (200 OK nhưng không nhận)

### **Check list:**

1. **Verify SMTP credentials:**

```bash
# Test bằng Tinker
php artisan tinker

use Illuminate\Support\Facades\Mail;
Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

2. **Check logs:**

```bash
# Xem log file
tail -f storage/logs/laravel.log
```

3. **Mailtrap inbox:**

- Login vào Mailtrap
- Check inbox đúng project
- Xem có email mới không

4. **Queue workers:**

```bash
# Nếu dùng queue, cần start worker:
php artisan queue:work
```

---

## Plugin Email Templates không hiện trong Admin

### **Giải pháp:**

1. **Clear cache:**

```bash
php artisan filament:cache-components
php artisan optimize:clear
```

2. **Check plugin registration:**

```php
// app/Providers/Filament/AdminPanelProvider.php
->plugins([
    \Visualbuilder\EmailTemplates\EmailTemplatesPlugin::make(),
])
```

3. **Verify permissions:**

- Login với Super Admin
- Check menu sidebar, tìm "Email Templates"

---

## Common Questions

### **Q: Email Templates plugin có license không?**

A: Plugin này là open-source, không cần license key.

### **Q: Có thể customize email templates không?**

A: Có! Vào Admin → Email Templates → Edit template bất kỳ.

### **Q: Làm sao để test không gửi email thật?**

A: Dùng Mailtrap (development) hoặc set `MAIL_MAILER=log`.

### **Q: Queue emails có tự động không?**

A: Không, cần thay `Mail::send()` bằng `Mail::queue()` và chạy worker.

---

## Quick Diagnostic Script

```bash
# Check all config
php artisan tinker

>> config('mail.mailers.smtp')
>> config('mail.from')
>> \App\Models\User::count()
>> \Illuminate\Support\Facades\Schema::hasTable('vb_email_templates')
```

---

**Updated**: 2026-01-30  
**Cần hỗ trợ thêm?** Check `docs/EMAIL_QUICK_START.md` hoặc `docs/API_EMAIL.md`
