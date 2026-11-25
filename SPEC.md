# Spec: Website BCL.VN — Blue Crane Logistics

Nguồn: `BCL_Yeu_Cau_Thiet_Ke_Website_2026.docx` + poster/banner nhận diện do BCL cung cấp (logo, slogan, hotline/Zalo, 5 nhóm dịch vụ).

## Objective
Website doanh nghiệp 3 ngôn ngữ (VI mặc định / EN / 中文, sẵn sàng thêm Khmer) cho **Công ty TNHH Tiếp Vận Sếu Xanh – Blue Crane Logistics Co., Ltd.**, mục tiêu:
1. Quảng bá dịch vụ hải quan, vận tải VN–KH, quá cảnh CN–VN–KH, Bắc–Nam, đường thủy, logistics trọn gói.
2. Tạo khách hàng tiềm năng qua form RFQ (có đính kèm chứng từ), nút gọi/Zalo/WhatsApp, nội dung chuẩn SEO.

Người dùng: chủ hàng/forwarder trong nước và quốc tế (VN, KH, CN) — phần lớn xem trên điện thoại.

## Tech Stack
- WordPress 6.x (PHP 8.3, MariaDB 11) chạy bằng Docker Compose; Mailpit bắt email khi chạy local.
- Polylang (free) — đa ngôn ngữ, URL theo thư mục: `/` (VI), `/en/`, `/zh/`.
- Theme tự viết `bcl` (không page builder, không jQuery ở frontend).
- Plugin tự viết `bcl-core` — post type Dịch vụ/Tuyến/RFQ, form RFQ, cài đặt công ty, SEO, bảo mật.

## Commands
```
Cài lần đầu + dữ liệu mẫu:  ./bin/setup.sh
Chạy:                       docker compose up -d
Dừng:                       docker compose down
WP-CLI:                     docker compose run --rm cli wp <lệnh>
Kiểm thử tự động:           ./bin/test.sh
Website:                    http://localhost:8088      Admin: /wp-admin
Hộp thư test (Mailpit):     http://localhost:8025
```

## Project Structure
```
docker-compose.yml, docker/php.ini     → môi trường chạy
bin/setup.sh, bin/test.sh              → cài đặt, seed, kiểm thử
seed/seed.php, seed/content.json       → dữ liệu mẫu 3 ngôn ngữ (idempotent)
wp-content/plugins/bcl-core/           → chức năng (giữ nguyên khi đổi theme)
wp-content/themes/bcl/                 → giao diện
docs/HANDOVER.md                       → hướng dẫn quản trị, sao lưu, danh sách plugin
```

## Nội dung & cấu trúc trang (mục 4–6 brief)
| Trang | VI | EN | ZH |
|---|---|---|---|
| Trang chủ | `/` | `/en/` | `/zh/` |
| Giới thiệu | `/gioi-thieu/` | `/en/about-us/` | `/zh/…/` |
| Dịch vụ (danh sách + 6 trang riêng) | `/services/…` | `/en/services/…` | `/zh/services/…` |
| Tuyến vận chuyển (danh sách + trang riêng) | `/routes/…` | `/en/routes/…` | `/zh/routes/…` |
| Tin tức / Kiến thức | `/tin-tuc/` | `/en/news/` | `/zh/…/` |
| Yêu cầu báo giá | `/yeu-cau-bao-gia/` | `/en/request-a-quote/` | `/zh/…/` |
| Liên hệ, Chính sách bảo mật | … | … | … |

6 dịch vụ: Thông quan hải quan · Vận tải Việt Nam – Campuchia · Hàng quá cảnh · Vận tải Bắc – Nam · Đường sông – sà lan · Xuất nhập khẩu & logistics trọn gói.

Tuyến có **trạng thái bắt buộc**: Đang khai thác / Theo yêu cầu / Thử nghiệm / Đang nghiên cứu. Tuyến chưa “Đang khai thác” hiển thị cảnh báo, không quảng cáo như dịch vụ thường xuyên.

## Form RFQ (mục 7)
- Trường: công ty, người liên hệ, email, điện thoại, WhatsApp/WeChat/Zalo; loại hàng, mã HS; điểm nhận/giao; khối lượng, số kiện, số container; loại xe; ngày vận chuyển; phạm vi dịch vụ; ghi chú; đồng ý xử lý dữ liệu cá nhân.
- Đính kèm: Invoice, Packing List, ảnh hàng — tối đa 5 file, mỗi file ≤ 10 MB, chỉ PDF/JPG/PNG/WEBP/DOC(X)/XLS(X), kiểm tra MIME thật, lưu ngoài thư mục công khai, chỉ admin tải được.
- Chống spam: honeypot + chặn gửi quá nhanh (token ký HMAC) + giới hạn 5 lần/giờ/IP + Cloudflare Turnstile (bật khi nhập key).
- Lưu vào admin (menu “Yêu cầu báo giá”, trạng thái xử lý, ghi chú nội bộ, xuất CSV), email cho BCL + email xác nhận cho khách theo ngôn ngữ, mã tham chiếu, sự kiện GA4 `generate_lead`.

## Success Criteria (nghiệm thu — mục 9)
- [ ] 3 ngôn ngữ đầy đủ mọi trang; switcher rõ ràng; nội dung mỗi ngôn ngữ sửa độc lập; `hreflang` đúng.
- [ ] Responsive 360px → 1440px, không cuộn ngang; menu mobile dùng được bằng bàn phím.
- [ ] Gửi RFQ thành công (có file) → bản ghi trong admin + email tới Mailpit (BCL + khách).
- [ ] File sai loại/quá dung lượng, honeypot, gửi quá nhanh đều bị từ chối với thông báo rõ.
- [ ] Nút gọi/Zalo/WhatsApp hoạt động và bắn sự kiện GA4 khi có Measurement ID.
- [ ] SEO: title/meta description/OG riêng từng trang, canonical, sitemap `/wp-sitemap.xml`, JSON-LD Organization/Service/Article/Breadcrumb.
- [ ] Logo gốc không bị đổi hình/tỷ lệ/màu; ảnh minh họa thay được bằng ảnh đại diện trong admin.
- [ ] Không lỗi PHP/console; Lighthouse mobile Performance/SEO/Accessibility ≥ 90 trên trang chủ (local).

## Boundaries
- Always: escape output, sanitize input, nonce/capability cho mọi thao tác admin; chuỗi giao diện qua gettext.
- Ask first: thêm plugin bên thứ ba ngoài Polylang; dữ liệu thật của khách hàng.
- Never: số liệu/tên đối tác/giải thưởng bịa; quảng cáo tuyến chưa xác nhận như dịch vụ thường xuyên; chỉnh logo.

## Open Questions (cần BCL cung cấp trước khi xuất bản)
1. Địa chỉ, email chính thức, tọa độ bản đồ.
2. Logo gốc dạng vector (AI/SVG) — hiện dùng bản cắt từ poster.
3. Trạng thái thực tế của từng tuyến (dữ liệu mẫu đang đặt tạm).
4. WhatsApp/WeChat (hiện mặc định WhatsApp = hotline 1, WeChat trống).
5. Ảnh thực tế xe, cửa khẩu, sà lan để thay ảnh minh họa Unsplash.
6. Bản dịch EN/ZH cần biên dịch viên hiệu đính.
