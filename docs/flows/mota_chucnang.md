# TÓM TẮT YÊU CẦU & MÔ TẢ CHỨC NĂNG HỆ THỐNG BĐS

## 1. Mục tiêu hệ thống
Xây dựng hệ thống quản lý BĐS gồm:
- **Web Admin** (Laravel 12 + Filament) cho quản trị và kiểm duyệt
- **API** cho App mobile/web client
- Quản lý dữ liệu BĐS, bài đăng, nhân sự, báo cáo
- Kiểm soát chặt chẽ dữ liệu nhạy cảm (SĐT chủ nhà, hồ sơ pháp lý)

---

## 2. Vai trò người dùng (Roles)

### 2.1 FIELD_STAFF (FS)
- Nhân viên thị trường / cộng tác viên
- Chức năng:
  - Tạo BĐS mới
  - Xem BĐS theo **khu vực được phân công**
  - Xem trạng thái duyệt BĐS của mình
  - Xin quyền xem SĐT chủ nhà
  - Gửi báo cáo vi phạm

### 2.2 OFFICE_ADMIN (OA)
- Nhân viên văn phòng / admin nghiệp vụ
- Chức năng:
  - Duyệt / từ chối BĐS
  - Quản lý bài đăng (Post)
  - Duyệt yêu cầu xem SĐT chủ
  - Xử lý báo cáo (Report)
  - Khóa / mở khóa tài khoản (theo quyền)

### 2.3 SUPER_ADMIN (SA)
- Admin tổng hệ thống
- Chức năng:
  - Quản lý user, role, phân quyền
  - Gán khu vực cho nhân sự
  - Quản lý danh mục (khu vực, dự án, loại BĐS…)
  - Xem audit log toàn hệ thống

---

## 3. Quy tắc nghiệp vụ cốt lõi

### 3.1 Scope theo khu vực
- FIELD_STAFF **chỉ được xem BĐS** thuộc các `area_ids` được gán.
- OFFICE_ADMIN / SUPER_ADMIN có thể xem toàn bộ hoặc lọc theo khu vực.

### 3.2 Che dữ liệu nhạy cảm (Masking)
- **SĐT chủ nhà (`owner_phone`)**
  - Mặc định **KHÔNG hiển thị** cho FIELD_STAFF
  - Chỉ hiển thị khi:
    - Là admin, hoặc
    - FIELD_STAFF đã có yêu cầu xem SĐT được duyệt
- **Hồ sơ pháp lý (`legal_docs`)**
  - Chỉ người đăng BĐS (created_by) và admin được xem

> Việc che dữ liệu bắt buộc thực hiện ở **Backend / API Resource**, không xử lý ở frontend.

---

## 4. Quản lý BĐS (Property)

### 4.1 Tạo BĐS
- FIELD_STAFF tạo BĐS mới
- Upload hình ảnh, hồ sơ pháp lý
- Trạng thái mặc định: `PENDING`

### 4.2 Duyệt BĐS
- OFFICE_ADMIN duyệt:
  - `APPROVED`: BĐS hợp lệ, có thể dùng để tạo bài đăng
  - `REJECTED`: từ chối, bắt buộc có lý do
- FIELD_STAFF theo dõi trạng thái trong “BĐS của tôi”

---

## 5. Xin quyền xem SĐT chủ nhà (OwnerPhoneRequest)

- FIELD_STAFF gửi yêu cầu xin xem SĐT chủ cho từng BĐS
- Quy tắc:
  - Không cho tạo **nhiều request PENDING** cho cùng một BĐS
- OFFICE_ADMIN xử lý:
  - `APPROVED`: cấp quyền xem SĐT cho đúng người xin
  - `REJECTED`: từ chối + lý do
- Quyền xem SĐT:
  - Gắn theo **FIELD_STAFF + PROPERTY**

---

## 6. Quản lý bài đăng (Post)

### 6.1 Tạo bài đăng
- Chỉ tạo Post từ BĐS đã `APPROVED`
- Nếu chưa duyệt → chặn thao tác

### 6.2 Trạng thái bài đăng
- `PENDING`
- `VISIBLE`
- `HIDDEN`
- `EXPIRED`

### 6.3 Quản lý bài đăng
- Sửa nội dung / giá
- Gia hạn hiển thị (`visible_until`)
- Ẩn bài
- Xóa bài:
  - Bắt buộc xác nhận
  - Ưu tiên soft delete

---

## 7. Báo cáo & kiểm duyệt (Reports)

### 7.1 Gửi báo cáo
- Người dùng gửi báo cáo:
  - Đối tượng: BĐS / bài đăng / người dùng
  - Mô tả + bằng chứng (ảnh/file)

### 7.2 Xử lý báo cáo
- OFFICE_ADMIN review và chọn hành động:
  - Ẩn bài đăng (`HIDE_POST`)
  - Khóa tài khoản (`LOCK_USER`)
  - Cảnh báo (`WARN`)
  - Không hành động
- Sau xử lý:
  - Report chuyển trạng thái `RESOLVED`
  - Ghi audit log

---

## 8. Tài khoản & bảo mật

- Đăng nhập / đăng xuất
- Quên mật khẩu qua OTP
- Đổi mật khẩu (yêu cầu mật khẩu cũ)
- Tài khoản bị khóa:
  - Không đăng nhập được
  - Không tạo BĐS / bài đăng mới

---

## 9. Upload & quản lý file

- File có:
  - `purpose`: avatar, ảnh BĐS, pháp lý, bằng chứng report…
  - `visibility`: PUBLIC / PRIVATE
- File PRIVATE:
  - Chỉ uploader hoặc admin được xem
  - Không public trực tiếp URL

---

## 10. Audit Log

- Ghi lại các hành động quan trọng:
  - Duyệt / từ chối BĐS
  - Duyệt / từ chối xin SĐT
  - Ẩn / xóa / gia hạn bài đăng
  - Xử lý báo cáo
  - Khóa / mở khóa user
- Audit log gồm:
  - Người thao tác
  - Hành động
  - Đối tượng
  - Thời gian
  - Dữ liệu thay đổi (payload)

---

## 11. Công nghệ & kiến trúc đề xuất

- Backend: **Laravel 12**
- Admin UI: **Filament v3**
- API: REST `/api/v1`
- Auth: JWT hoặc Sanctum
- Database: MySQL
- Frontend build: Vite
- Testing: Feature test cho scope, masking, workflow

---

## 12. Nguyên tắc triển khai
- Business logic nằm ở Backend
- Filament chỉ là UI gọi logic
- Policy & Service tách rõ ràng
- Ưu tiên maintainable hơn nhanh chóng

---
