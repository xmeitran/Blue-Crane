# BCL.VN — Blue Crane Logistics website

Website 3 ngôn ngữ (Tiếng Việt / English / 中文) cho **Công ty TNHH Tiếp Vận Sếu Xanh – Blue Crane Logistics Co., Ltd.**
WordPress + Polylang, theme `bcl` và plugin `bcl-core` viết riêng.

- Yêu cầu & tiêu chí nghiệm thu: [SPEC.md](SPEC.md)
- Hướng dẫn quản trị, triển khai, sao lưu, danh sách plugin: [docs/HANDOVER.md](docs/HANDOVER.md)

```bash
./bin/setup.sh   # Docker: WordPress + Polylang + nội dung mẫu 3 ngôn ngữ → http://localhost:8090
./bin/test.sh    # kiểm thử end-to-end (trang, SEO, form RFQ, email, bảo mật tệp)
```

| Thư mục | Nội dung |
|---|---|
| `wp-content/themes/bcl/` | Giao diện |
| `wp-content/plugins/bcl-core/` | Dịch vụ, tuyến, RFQ, cài đặt công ty, SEO, bảo mật |
| `seed/` | Nội dung mẫu 3 ngôn ngữ + script nạp dữ liệu |
| `bin/` | Cài đặt, kiểm thử, xuất demo tĩnh |
