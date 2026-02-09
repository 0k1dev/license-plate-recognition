# Flow 3 — Xem BĐS theo khu vực + che dữ liệu nhạy cảm (scope + masking)

## Mục tiêu
- FS chỉ xem BĐS thuộc khu vực được gán (`area_ids`)
- Che dữ liệu nhạy cảm (`owner_phone`, `legal_docs`) theo quyền

## Mô tả luồng
1. FS gọi `GET properties`:
   - Hệ thống lọc theo `area_ids` của FS
2. FS mở chi tiết BĐS `GET properties/{id}`:
   - Nếu chưa được cấp quyền → `owner_phone=null` (vẫn có thể thấy `holder_phone`)
   - Nếu đã được duyệt request xem số → hiển thị `owner_phone`

## Mermaid
```mermaid
flowchart TD
  A([Bắt đầu]) --> FS0[FS xem danh sách BĐS]
  FS0 --> FS1[GET properties (lọc theo area_ids)]
  FS1 --> FS2[FS mở chi tiết BĐS: GET properties/id]
  FS2 --> Q{FS có quyền xem SĐT chủ?}
  Q --> Q1[Không -> owner_phone = null; show holder_phone]
  Q --> Q2[Có -> hiển thị owner_phone]

  Q1 --> Z([Kết thúc])
  Q2 --> Z
```
