# Flow 5 — Vòng đời bài đăng (Post lifecycle)

## Mục tiêu
- Chỉ đăng bài từ BĐS đã duyệt
- Quản lý hiển thị/ẩn/gia hạn/xóa có xác nhận

## Mô tả luồng
1. OA tạo Post từ Property:
   - Nếu Property chưa `APPROVED` → chặn `PROPERTY_NOT_APPROVED`
   - Nếu hợp lệ → tạo Post
2. OA quản lý Post:
   - Sửa nội dung/giá
   - Gia hạn hiển thị (`renew` tăng `visible_until`)
   - Ẩn bài (`hide` → `HIDDEN`)
   - Xóa bài (yêu cầu xác nhận)

## Mermaid
```mermaid
flowchart TD
  A([Bắt đầu]) --> OA0[OA tạo bài đăng từ BĐS]
  OA0 --> Q{BĐS đã APPROVED chưa?}
  Q --> Q1[Chưa -> Chặn (PROPERTY_NOT_APPROVED)] --> Z([Kết thúc])
  Q --> Q2[Rồi -> POST posts -> tạo Post]

  Q2 --> OA1[OA quản lý Post]
  OA1 --> OA2{Chọn thao tác}
  OA2 --> E1[Sửa nội dung/giá]
  OA2 --> E2[Gia hạn hiển thị]
  OA2 --> E3[Ẩn bài]
  OA2 --> E4[Xóa bài]

  E1 --> P1[PATCH posts/id] --> OA1
  E2 --> P2[POST posts/id/renew -> tăng visible_until] --> OA1
  E3 --> P3[POST posts/id/hide -> status HIDDEN] --> OA1

  E4 --> C1{Có xác nhận xóa?}
  C1 --> C2[Không -> Chặn (POST_DELETE_CONFIRM_REQUIRED)] --> OA1
  C1 --> C3[Có -> DELETE posts/id] --> OA1

  OA1 --> Z
```
