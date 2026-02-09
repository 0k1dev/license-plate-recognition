# 📚 DOCUMENTATION INDEX

Thư mục `/docs` chứa toàn bộ tài liệu hệ thống BĐS.

---

## 📂 Cấu Trúc

### **1. Flows (Luồng nghiệp vụ)**

Các file trong `flows/` mô tả chi tiết từng workflow:

| File                                   | Mô tả                                        |
| -------------------------------------- | -------------------------------------------- |
| `mota_chucnang.md`                     | Tổng quan hệ thống, roles, business rules    |
| `01-auth-va-trang-thai-tai-khoan.md`   | Authentication, OTP, account status          |
| `02-vong-doi-bds-tao-duyet-tu-choi.md` | Property lifecycle (create → approve/reject) |
| `03-xem-bds-scope-masking.md`          | Scope filtering & data masking               |
| `04-xin-quyen-xem-sdt-chu.md`          | Owner phone request workflow                 |
| `05-vong-doi-bai-dang-post.md`         | Post lifecycle management                    |
| `06-bao-cao-va-kiem-duyet.md`          | Report & moderation workflow                 |

---

### **2. Email System**

| File                           | Mô tả                               |
| ------------------------------ | ----------------------------------- |
| `EMAIL_QUICK_START.md`         | ⚡ Quick start guide (5 phút setup) |
| `EMAIL_CONFIGURATION.md`       | 📧 Hướng dẫn cấu hình SMTP chi tiết |
| `API_EMAIL.md`                 | 📖 API documentation với examples   |
| `EMAIL_INTEGRATION_SUMMARY.md` | 📊 Tổng hợp toàn bộ tích hợp        |

**Bắt đầu từ**: `EMAIL_QUICK_START.md`

---

### **3. Progress Report**

| File                 | Mô tả                                        |
| -------------------- | -------------------------------------------- |
| `PROGRESS_REPORT.md` | 📊 Báo cáo tiến độ chi tiết (92% hoàn thành) |

Xem để biết:

- Những gì đã làm (✅)
- Những gì thiếu (⚠️)
- Roadmap tiếp theo

---

## 🚀 Quick Links

### **Cho Developer Mới**

1. Đọc `PROGRESS_REPORT.md` để hiểu overview
2. Đọc `flows/mota_chucnang.md` để hiểu business
3. Follow `EMAIL_QUICK_START.md` để setup email

### **Cho Frontend/Mobile Developer**

1. Đọc `API_EMAIL.md` cho email APIs
2. Xem Postman collection: `/storage/postman/collection.json`
3. Đọc workflows trong `flows/` để hiểu flows

### **Cho DevOps**

1. `EMAIL_CONFIGURATION.md` - SMTP setup
2. `PROGRESS_REPORT.md` section "DevOps & Deployment"

---

## 📝 Tài Liệu Còn Thiếu

- [ ] Swagger/OpenAPI spec
- [ ] Database schema diagram
- [ ] Deployment guide
- [ ] Testing strategy
- [ ] CI/CD pipeline docs

---

## 🔄 Cập Nhật

| Ngày       | Thay đổi                              |
| ---------- | ------------------------------------- |
| 2026-01-30 | ✅ Thêm email system docs (4 files)   |
| 2026-01-30 | ✅ Update PROGRESS_REPORT (Email 90%) |
| 2026-01-23 | ✅ Thêm API flows documentation       |

---

**Lưu ý**: Tài liệu được cập nhật liên tục. Check git history để xem changes mới nhất.
