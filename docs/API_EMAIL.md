# 📧 EMAIL API DOCUMENTATION

## Base URL

```
POST /api/v1/emails/*
```

**Authentication**: Bearer Token (Sanctum)  
**Content-Type**: application/json  
**API-Key**: Required in header

---

## 1. Gửi OTP Email

### Endpoint

```
POST /api/v1/emails/send-otp
```

### Request Body

```json
{
    "email": "user@example.com",
    "otp": "123456",
    "expires_in": 5
}
```

### Parameters

| Field      | Type    | Required | Description                              |
| ---------- | ------- | -------- | ---------------------------------------- |
| email      | string  | ✅       | Email người nhận (phải tồn tại trong DB) |
| otp        | string  | ✅       | Mã OTP 6 số                              |
| expires_in | integer | ❌       | Thời gian hết hạn (phút), mặc định: 5    |

### Success Response (200)

```json
{
    "success": true,
    "message": "Email OTP đã được gửi thành công."
}
```

### Error Response (422)

```json
{
    "success": false,
    "errors": {
        "email": ["Email không tồn tại trong hệ thống."]
    }
}
```

### cURL Example

```bash
curl -X POST http://localhost:8000/api/v1/emails/send-otp \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp": "123456",
    "expires_in": 5
  }'
```

---

## 2. Gửi Email Property Approved

### Endpoint

```
POST /api/v1/emails/property-approved
```

### Request Body

```json
{
    "property_id": 1
}
```

### Parameters

| Field       | Type    | Required | Description              |
| ----------- | ------- | -------- | ------------------------ |
| property_id | integer | ✅       | ID của BĐS đã được duyệt |

### Success Response (200)

```json
{
    "success": true,
    "message": "Email thông báo BĐS được duyệt đã gửi thành công."
}
```

### Error Response (400)

```json
{
    "success": false,
    "message": "User không có email."
}
```

### cURL Example

```bash
curl -X POST http://localhost:8000/api/v1/emails/property-approved \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb" \
  -H "Content-Type: application/json" \
  -d '{
    "property_id": 1
  }'
```

---

## 3. Gửi Email Property Rejected

### Endpoint

```
POST /api/v1/emails/property-rejected
```

### Request Body

```json
{
    "property_id": 1,
    "reason": "Thông tin BĐS chưa đầy đủ. Vui lòng bổ sung hình ảnh pháp lý."
}
```

### Parameters

| Field       | Type    | Required | Description                   |
| ----------- | ------- | -------- | ----------------------------- |
| property_id | integer | ✅       | ID của BĐS bị từ chối         |
| reason      | string  | ✅       | Lý do từ chối (max 500 ký tự) |

### Success Response (200)

```json
{
    "success": true,
    "message": "Email thông báo BĐS bị từ chối đã gửi thành công."
}
```

### cURL Example

```bash
curl -X POST http://localhost:8000/api/v1/emails/property-rejected \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb" \
  -H "Content-Type: application/json" \
  -d '{
    "property_id": 1,
    "reason": "Thông tin BĐS chưa đầy đủ"
  }'
```

---

## 4. Gửi Email Phone Request Approved

### Endpoint

```
POST /api/v1/emails/phone-request-approved
```

### Request Body

```json
{
    "request_id": 1
}
```

### Parameters

| Field      | Type    | Required | Description                       |
| ---------- | ------- | -------- | --------------------------------- |
| request_id | integer | ✅       | ID của OwnerPhoneRequest đã duyệt |

### Success Response (200)

```json
{
    "success": true,
    "message": "Email thông báo yêu cầu xem SĐT được duyệt đã gửi thành công."
}
```

### cURL Example

```bash
curl -X POST http://localhost:8000/api/v1/emails/phone-request-approved \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb" \
  -H "Content-Type: application/json" \
  -d '{
    "request_id": 1
  }'
```

---

## 5. Gửi Custom Email

### Endpoint

```
POST /api/v1/emails/custom
```

### Request Body

```json
{
    "to": "user@example.com",
    "subject": "Thông báo quan trọng",
    "message": "Nội dung email..."
}
```

### Parameters

| Field   | Type   | Required | Description                 |
| ------- | ------ | -------- | --------------------------- |
| to      | string | ✅       | Email người nhận            |
| subject | string | ✅       | Tiêu đề email (max 255)     |
| message | string | ✅       | Nội dung email (plain text) |

### Success Response (200)

```json
{
    "success": true,
    "message": "Email đã được gửi thành công."
}
```

### cURL Example

```bash
curl -X POST http://localhost:8000/api/v1/emails/custom \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-KEY: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "user@example.com",
    "subject": "Test Email",
    "message": "This is a test message"
  }'
```

---

## Common Errors

### 401 Unauthorized

```json
{
    "message": "Unauthenticated."
}
```

**Giải pháp**: Kiểm tra Bearer token

### 403 Forbidden

```json
{
    "message": "Invalid API Key"
}
```

**Giải pháp**: Kiểm tra header `X-API-KEY`

### 500 Internal Server Error

```json
{
    "success": false,
    "message": "Không thể gửi email. Vui lòng thử lại sau.",
    "error": "Connection timeout..."
}
```

**Giải pháp**: Kiểm tra cấu hình SMTP

---

## Testing với Postman

1. Import collection từ `/storage/postman/collection.json`
2. Set environment variables:
    - `base_url`: http://localhost:8000
    - `api_key`: bds-TTMIxTtE1H6MXIypiiBoa1IfpPA3D0Nb
    - `access_token`: (sau khi login)
3. Test từng endpoint

---

## Rate Limiting

Mỗi email endpoint có rate limit:

- **Throttle**: 10 requests / phút
- **Daily limit**: 500 emails / ngày (tùy SMTP provider)

---

## Best Practices

1. ✅ **Queue emails** thay vì gửi đồng bộ
2. ✅ **Log email failures** để troubleshoot
3. ✅ **Validate email format** trước khi gửi
4. ✅ **Use templates** thay vì hard-code content
5. ✅ **Test với Mailtrap** trước khi production

---

Cập nhật: 2026-01-30
