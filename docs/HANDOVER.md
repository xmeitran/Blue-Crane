# Bàn giao website BCL.VN

Tài liệu cho đội BCL (quản trị nội dung) và đơn vị vận hành hosting. Phạm vi và tiêu chí nghiệm thu: [SPEC.md](../SPEC.md).

## 1. Cập nhật nội dung (wp-admin)

| Việc cần làm | Vào đâu |
|---|---|
| Hotline, Zalo, WhatsApp, WeChat, email, địa chỉ, bản đồ, email nhận RFQ, GA4, Search Console, Turnstile | **Cài đặt BCL** |
| Banner trang chủ (dòng nhỏ, tiêu đề, mô tả), ảnh hoạt động, logo đối tác | **Trang → Trang chủ** (từng ngôn ngữ) → hộp “Trang chủ: banner…”. Ảnh nền banner = *Ảnh đại diện* |
| Giới thiệu, Liên hệ, Yêu cầu báo giá, Chính sách bảo mật | **Trang** |
| Dịch vụ (6 trang riêng) — biểu tượng, các ý nổi bật, ảnh | **Dịch vụ** |
| Tuyến vận chuyển — điểm đi/đến, cửa khẩu, phương tiện, **trạng thái** | **Tuyến vận chuyển** |
| Tin tức / Kiến thức | **Bài viết** (chuyên mục: Chính sách hải quan, Hoạt động cửa khẩu, Hướng dẫn XNK, Tin công ty) |
| Menu | **Giao diện → Menu** (mỗi ngôn ngữ một menu) |
| Logo, favicon | **Giao diện → Tùy biến → Nhận diện site** (logo đang dùng bản cắt từ poster) |
| Chuỗi giao diện (nút, nhãn form) | File `languages/*.l10n.php` trong theme/plugin, hoặc cài *Loco Translate* để sửa trong admin |

**Đa ngôn ngữ (Polylang).** Mỗi bài/trang/dịch vụ/tuyến có 3 bản VI/EN/ZH độc lập. Ở danh sách, cột cờ: ✎ = sửa bản dịch, **+** = tạo bản dịch. Các trường không phụ thuộc ngôn ngữ (trạng thái tuyến, phương tiện, biểu tượng, ảnh đại diện, thứ tự) tự đồng bộ giữa các bản.

**Thêm tiếng Khmer:** Ngôn ngữ → Thêm ngôn ngữ mới (ខ្មែរ, `km`) → tạo bản dịch cho từng nội dung → thêm `km.l10n.php` cho chuỗi giao diện. Nút chuyển ngôn ngữ tự hiện “ខ្មែរ”.

**Ảnh.** Ảnh hiện tại là ảnh minh họa Unsplash (miễn phí bản quyền) — chỉ hiển thị khi nội dung chưa có *Ảnh đại diện*. Thay bằng ảnh thực tế của BCL: đặt Ảnh đại diện cho từng dịch vụ/tuyến/bài viết, chọn “Ảnh hoạt động” ở trang chủ. Danh sách ảnh minh họa: `wp-content/plugins/bcl-core/includes/images.php`.

**Trạng thái tuyến (bắt buộc ghi đúng).** Đang khai thác / Theo yêu cầu / Thử nghiệm / Đang nghiên cứu. Tuyến chưa “Đang khai thác” tự hiện cảnh báo “chưa phải dịch vụ thường xuyên”. ⚠️ Trạng thái hiện tại là **dữ liệu mẫu** — BCL xác nhận lại trước khi xuất bản.

## 2. Yêu cầu báo giá (RFQ)

- Menu **Yêu cầu báo giá**: danh sách có mã (RFQ-YYMMDD-xxxx), lọc theo trạng thái (Mới → Đang xử lý → Đã báo giá → Thành công / Đóng), ghi chú nội bộ, tải tệp đính kèm, nút **Xuất CSV** (mở được bằng Excel).
- Mỗi yêu cầu gửi 2 email: thông báo cho BCL (tiếng Việt, Reply-To = khách) và xác nhận cho khách (theo ngôn ngữ khách dùng).
- Tệp đính kèm: tối đa 5 tệp × 10 MB; PDF/JPG/PNG/WEBP/DOC(X)/XLS(X); kiểm tra MIME thật; lưu ở `wp-content/uploads/bcl-rfq/` với tên ngẫu nhiên, chặn truy cập trực tiếp; chỉ người có quyền mới tải được. Xóa vĩnh viễn yêu cầu → xóa luôn tệp.
- Chống spam: honeypot, chặn gửi < 3 giây, giới hạn 5 yêu cầu/giờ/IP, Cloudflare Turnstile (nhập key ở Cài đặt BCL để bật).
- Lưu thời điểm khách đồng ý xử lý dữ liệu cá nhân + link chính sách.
- Mở rộng CRM: hook `do_action( 'bcl_rfq_submitted', $rfq_id, $values, $files )`.

**Phân quyền:** Quản trị viên (toàn quyền) · Biên tập viên (nội dung + RFQ) · **Nhân viên kinh doanh (RFQ)** — chỉ xem/xử lý RFQ, không sửa nội dung, không xóa.

## 3. SEO & đo lường

- URL riêng theo ngôn ngữ: `/` (VI), `/en/`, `/zh/`; thẻ `hreflang`, canonical tự động.
- Mỗi trang có hộp **SEO** (tiêu đề ≤ 60 ký tự, mô tả 120–155 ký tự); Open Graph; JSON-LD Organization, Service, BlogPosting, Breadcrumb.
- Sitemap: `/wp-sitemap.xml` → khai báo trong Google Search Console.
- GA4: nhập Measurement ID → tự có sự kiện `generate_lead` (gửi RFQ thành công), `click_call`, `click_zalo`, `click_whatsapp`, `click_email`, `click_rfq`. Đánh dấu `generate_lead` là *Key event* trong GA4.

## 4. Đưa lên hosting thật

Yêu cầu: PHP ≥ 8.1, MySQL/MariaDB, HTTPS (Let’s Encrypt), Apache hoặc Nginx.

1. Cài WordPress, cài & kích hoạt **Polylang**, chép `wp-content/themes/bcl` và `wp-content/plugins/bcl-core`, kích hoạt plugin BCL Core rồi theme BCL.
2. Nội dung: hoặc chạy `wp eval-file seed/seed.php` (cần `seed/content.json`), hoặc chuyển DB từ bản local rồi `wp search-replace 'http://localhost:8090' 'https://www.bcl.vn' --all-tables`.
3. `wp-config.php`: `define('DISALLOW_FILE_EDIT', true);` và SMTP để email không vào spam:
   ```php
   define('BCL_SMTP_HOST', 'smtp.example.com'); define('BCL_SMTP_PORT', 587);
   define('BCL_SMTP_USER', '...'); define('BCL_SMTP_PASS', '...'); define('BCL_SMTP_SECURE', 'tls');
   ```
   Email gửi đi lấy từ “Email công khai” ở Cài đặt BCL (cấu hình SPF/DKIM cho tên miền).
4. **Nginx** không đọc `.htaccess` → thêm: `location ^~ /wp-content/uploads/bcl-rfq/ { deny all; }`
5. Sau Cloudflare/proxy: sửa `bcl_rfq_ip()` (plugin, `includes/rfq.php`) để lấy IP thật cho giới hạn chống spam.
6. Cài đặt → Đọc: bỏ chọn “Ngăn công cụ tìm kiếm”. Cài đặt → Đường dẫn tĩnh: “Tên bài viết”.
7. Điền Cài đặt BCL (địa chỉ, email, bản đồ, email nhận RFQ, GA4, GSC). Gửi thử 1 RFQ.

## 5. Sao lưu, bảo mật, cập nhật

- **Sao lưu:** hằng ngày DB + `wp-content/uploads` (gồm tệp RFQ), giữ ≥ 14 bản, lưu ngoài máy chủ (Google Drive/S3) — dùng sao lưu của hosting hoặc plugin *UpdraftPlus*. **Khôi phục thử** mỗi quý.
- **Cập nhật:** WordPress, Polylang hàng tháng (bật tự động cập nhật bản vá bảo mật). Theme/plugin BCL cập nhật qua mã nguồn Git.
- **Đăng nhập:** mật khẩu mạnh + xác thực 2 lớp (plugin *Two Factor*), giới hạn đăng nhập sai (*Limit Login Attempts Reloaded* hoặc WAF của hosting/Cloudflare).
- Đã có sẵn trong mã: tắt XML-RPC, ẩn phiên bản WP, chặn dò tên người dùng, header bảo mật (nosniff, SAMEORIGIN, Referrer-Policy, HSTS khi HTTPS), không sửa file trong admin.

## 6. Chạy bản local (đơn vị phát triển)

```bash
./bin/setup.sh      # cài WordPress + Polylang + theme/plugin + nội dung 3 ngôn ngữ
./bin/test.sh       # 32 kiểm thử: trang, SEO, RFQ (hợp lệ, spam, file giả), email, chặn tệp
./bin/export-static.sh  # xuất bản demo tĩnh (GitHub Pages) để khách duyệt giao diện
```
Website `http://localhost:8090` · Admin `/wp-admin` (tài khoản trong `.env`) · Hộp thư test `http://localhost:8025`.

## 7. Danh sách plugin, thư viện & giấy phép

| Thành phần | Vai trò | Giấy phép / chi phí |
|---|---|---|
| WordPress 7.x | CMS | GPL v2+, miễn phí |
| Polylang (bản miễn phí) | Đa ngôn ngữ | GPL, miễn phí. *Polylang Pro* (trả phí) nếu muốn dịch slug `/services/`, `/routes/` theo ngôn ngữ |
| Theme **BCL** | Giao diện | Viết riêng, thuộc BCL |
| Plugin **BCL Core** | Dịch vụ, tuyến, RFQ, SEO, bảo mật | Viết riêng, thuộc BCL |
| Be Vietnam Pro (Google Fonts) | Font chữ | SIL Open Font License, miễn phí |
| Ảnh minh họa Unsplash | Tạm thời, thay bằng ảnh BCL | Unsplash License, miễn phí |
| Mailpit, Docker | Chỉ dùng khi phát triển | MIT / Apache 2.0 |

Không dùng page builder, không jQuery ở giao diện, không plugin trả phí.

## 8. Việc BCL cần cung cấp trước khi xuất bản

1. Địa chỉ, email chính thức, link Google Maps.
2. Logo gốc dạng vector (AI/SVG/PDF) — bản hiện tại cắt từ poster, đủ dùng cho web nhưng nên thay bằng file gốc.
3. Xác nhận trạng thái từng tuyến; WhatsApp có dùng số 0915 713 979 không; WeChat ID (nếu có).
4. Ảnh thực tế: xe, container, cửa khẩu, sà lan, kho bãi, đội ngũ.
5. Biên dịch viên hiệu đính bản EN/ZH; tên tiếng Trung chính thức của công ty (nếu có).
6. Logo đối tác được phép đăng.
