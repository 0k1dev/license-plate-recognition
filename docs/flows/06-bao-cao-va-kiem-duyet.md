# Flow 6 — Báo cáo & xử lý vi phạm (Reports & moderation)

## Mục tiêu
- Tiếp nhận report và áp dụng biện pháp kiểm duyệt
- Lưu lịch sử xử lý (audit log)

## Mô tả luồng
1. Người dùng gửi report: `POST reports` (đối tượng + mô tả + bằng chứng).
2. OA review report:
   - Ẩn bài (`HIDE_POST`)
   - Khóa user (`LOCK_USER`)
   - Cảnh báo (`WARN`)
   - Không hành động (`NO_ACTION`)
3. Report chuyển `RESOLVED` và ghi audit log.

## Mermaid
```mermaid
flowchart TD
  A([Bắt đầu]) --> U0[Người dùng gửi báo cáo]
  U0 --> U1[POST reports (đối tượng + mô tả + bằng chứng)]
  U1 --> U2[Report trạng thái NEW]

  U2 --> OA0[OA xem report NEW/IN_REVIEW]
  OA0 --> OA1[Review chi tiết report]
  OA1 --> OA2{Chọn biện pháp xử lý}
  OA2 --> A1[Ẩn bài đăng liên quan]
  OA2 --> A2[Khóa tài khoản vi phạm]
  OA2 --> A3[Cảnh báo và ghi chú]
  OA2 --> A4[Không hành động (ghi nhận)]

  A1 --> R1[Post -> HIDDEN]
  A2 --> R2[User -> LOCKED]
  A3 --> R3[Lưu note cảnh báo]
  A4 --> R4[Lưu note]

  R1 --> S1[Report -> RESOLVED + ghi AuditLog]
  R2 --> S1
  R3 --> S1
  R4 --> S1

  S1 --> Z([Kết thúc])
```
