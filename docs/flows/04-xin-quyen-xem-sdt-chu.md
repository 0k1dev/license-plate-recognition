# Flow 4 — Xin quyền xem SĐT chủ nhà (OwnerPhoneRequest)

## Mục tiêu
- Kiểm soát việc FS tiếp cận chủ nhà
- Chống spam (không tạo trùng request đang `PENDING`)

## Mô tả luồng
1. FS gửi yêu cầu `POST properties/{id}/owner-phone-requests` kèm lý do.
2. Nếu đã có request `PENDING` cho cùng BĐS → chặn `OWNER_PHONE_REQUEST_DUPLICATED`.
3. OA duyệt:
   - Approve → `APPROVED` và cấp quyền xem `owner_phone` cho đúng requester
   - Reject → `REJECTED` + lý do
4. Khi FS xem lại chi tiết BĐS:
   - Nếu được duyệt → thấy `owner_phone`
   - Nếu không → vẫn bị che

## Mermaid
```mermaid
flowchart TD
  A([Bắt đầu]) --> FS0[FS gửi yêu cầu xem SĐT chủ]
  FS0 --> FS1[POST properties/id/owner-phone-requests (kèm lý do)]
  FS1 --> Q{Đã có request PENDING cho BĐS này?}
  Q --> Q1[Có -> Chặn (OWNER_PHONE_REQUEST_DUPLICATED)] --> Z([Kết thúc])
  Q --> Q2[Không -> Tạo request trạng thái PENDING]

  Q2 --> OA0[OA xem danh sách request PENDING]
  OA0 --> OA1{Duyệt request?}
  OA1 --> OA2[Duyệt -> APPROVED + cấp quyền xem]
  OA1 --> OA3[Từ chối -> REJECTED + lý do]

  OA2 --> FS2[FS xem lại BĐS -> owner_phone hiển thị]
  OA3 --> FS3[FS vẫn không xem được owner_phone]

  FS2 --> Z
  FS3 --> Z
```
