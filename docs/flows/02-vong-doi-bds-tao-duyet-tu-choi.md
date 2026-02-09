# Flow 2 — Vòng đời BĐS (tạo → duyệt → từ chối)

## Mục tiêu
- BĐS tạo mới phải qua kiểm duyệt trước khi sử dụng để đăng bài

## Mô tả luồng
1. **FIELD_STAFF (FS)** tạo BĐS:
   - Upload ảnh/pháp lý (Files)
   - Tạo BĐS với `approval_status=PENDING`
2. **OFFICE_ADMIN (OA)** kiểm duyệt:
   - Duyệt → `APPROVED`
   - Từ chối → `REJECTED` + lý do
3. FS theo dõi trạng thái trong danh sách “BĐS của tôi”.

## Mermaid
```mermaid
flowchart TD
  A([Bắt đầu]) --> FS0[FS tạo BĐS]
  FS0 --> FS1[Upload ảnh/pháp lý (Files)]
  FS1 --> FS2[POST properties -> tạo BĐS trạng thái PENDING]

  FS2 --> OA0[OA xem danh sách BĐS PENDING]
  OA0 --> OA1{Quyết định duyệt?}
  OA1 --> OA2[Duyệt -> APPROVED]
  OA1 --> OA3[Từ chối -> REJECTED + lý do]

  OA2 --> FS3[FS xem lại BĐS của tôi (đã duyệt)]
  OA3 --> FS4[FS xem lý do bị từ chối]

  FS3 --> Z([Kết thúc])
  FS4 --> Z
```
