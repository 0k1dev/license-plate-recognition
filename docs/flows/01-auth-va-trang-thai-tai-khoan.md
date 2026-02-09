# Flow 1 — Xác thực & trạng thái tài khoản

## Mục tiêu
- Đăng nhập hệ thống
- Hỗ trợ quên mật khẩu (OTP)
- Chặn tài khoản bị khóa

## Mô tả luồng
1. Người dùng chọn **Đăng nhập** hoặc **Quên mật khẩu**.
2. **Đăng nhập**:
   - Gọi `POST auth/login`
   - Nếu tài khoản bị khóa → chặn truy cập
   - Nếu hợp lệ → cấp token/phiên và điều hướng theo vai trò (FS/OA/SA)
3. **Quên mật khẩu**:
   - Yêu cầu OTP → xác thực OTP → đặt lại mật khẩu → quay lại đăng nhập

## Mermaid
```mermaid
flowchart TD
  A([Bắt đầu]) --> B{Người dùng muốn làm gì?}

  B --> B1[Đăng nhập]
  B --> B2[Quên mật khẩu]

  B1 --> C[POST auth/login]
  C --> D{Tài khoản bị khóa?}
  D --> D1[Có -> Chặn truy cập (AUTH_ACCOUNT_LOCKED)] --> Z([Kết thúc])
  D --> D2[Không -> Cấp token/phiên]
  D2 --> E{Vai trò?}
  E --> E1[FS -> vào App]
  E --> E2[OA -> vào Admin (Filament)]
  E --> E3[SA -> vào Admin (Filament)]
  E1 --> Z
  E2 --> Z
  E3 --> Z

  B2 --> F[POST auth/forgot-password]
  F --> G[POST auth/verify-otp]
  G --> H[POST auth/reset-password]
  H --> C
```
