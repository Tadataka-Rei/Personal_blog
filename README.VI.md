<p align="center">
  <img src="frontend/public/img/icon.png" alt="Icon" width="10%">
</p>

# <p align="center">Tevel's Dev Blog</p>
## Ngôn ngữ

[![English](https://img.shields.io/badge/lang-en-blue.svg)](https://github.com/Tadataka-Rei/Personal_blog/blob/main/README.md)
---
Một nền tảng blog cá nhân có giao diện tối được xây dựng cho **Tevel** (tôi). Dự án kết hợp một **giao diện tĩnh siêu nhẹ** (HTML/CSS/vanilla JS) với một **bảng quản trị PHP** để quản lý đầy đủ các thao tác CRUD cho các bài viết trên blog.
<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.1-777BB4?logo=php)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker)
![License](https://img.shields.io/badge/License-All%20Rights%20Reserved-red)

</div>

Các bài viết được lưu trữ dưới dạng **các tệp JSON được tổ chức theo tháng**, đi kèm với một danh mục `post-count.js` đã được tạo để hỗ trợ phân trang tải lười (lazy-loaded) cực nhanh ở phía giao diện người dùng. Mọi thứ hoạt động thông qua **Docker Compose** với hai dịch vụ: một backend PHP 8.1 FPM + nginx (quản trị) và một giao diện tĩnh `nginx:alpine`.

<details>
<summary><strong>💡 Tại sao dùng JSON thay vì cơ sở dữ liệu?</strong></summary>

Dự án này chủ yếu lưu trữ các bài viết trong các tệp JSON theo tháng thay vì sử dụng MySQL hay SQLite.

- Dự án này được thiết kế để chạy trên một chiếc TV Box RAM 2GB
- Không cần thiết lập cơ sở dữ liệu
- Sử dụng RAM tối thiểu
- Dễ dàng sao lưu
- Hoàn hảo cho các blog chỉ có một quản trị viên
- Lý tưởng cho phần cứng cấu hình thấp

Bảng quản trị vẫn cung cấp đầy đủ các tính năng CRUD trong khi giao diện người dùng vẫn hoàn toàn tĩnh.

</details>


---
## 📑 Mục lục
- [📸 Ảnh chụp màn hình](#-ảnh-chụp-màn-hình)
- [✨ Tính năng](#-tính-năng)
  - [🌐 Giao diện người dùng](#-giao-diện-người-dùng-tĩnh-không-cần-bước-build)
  - [🔐 Bảng quản trị](#-bảng-quản-trị-php-backend)
- [📁 Cấu trúc dự án](#-cấu-trúc-dự-án)
- [🚀 Chạy cục bộ](#-chạy-cục-bộ)
  - [Yêu cầu tiên quyết](#yêu-cầu-tiên-quyết)
  - [Các bước thực hiện](#các-bước-thực-hiện)
  - [Chạy không cần Docker](#chạy-không-cần-docker-tùy-chọn)
- [🔧 Biến môi trường](#-biến-môi-trường)
- [🎨 Bảng mã màu](#-bảng-mã-màu)
  - [Bảng màu cốt lõi](#bảng-màu-cốt-lõi)
  - [Màu trạng thái quản trị](#màu-trạng-thái-quản-trị)
  - [Gradient nền](#gradient-nền)
- [🚢 Triển khai](#-triển-khai)
  - [Bản dựng sản xuất](#bản-dựng-sản-xuất-docker)
  - [Reverse Proxy](#reverse-proxy-tùy-chọn)
  - [Các lưu ý khi triển khai thực tế](#các-lưu-ý-khi-triển-khai-thực-tế)
- [⭐ Hỗ trợ](#-hỗ-trợ)
- [📝 Giấy phép](#-giấy-phép)

---
## 📸 Ảnh chụp màn hình

### Trang chủ

<p align="center">
  <img src="docs/img/home.png" alt="Trang chủ">
</p>

### Blog

<p align="center">
  <img src="docs/img/about.png" alt="Blog">
</p>

### Bảng điều khiển quản trị

<p align="center">
  <img src="docs/img/dashboard.png" alt="Bảng điều khiển">
</p>

### Trình soạn thảo

<p align="center">
  <img src="docs/img/editor.png" alt="Trình soạn thảo">
</p>

---
## ✨ Tính năng

### 🌐 Giao diện người dùng (tĩnh, không cần bước build)

- **Trang chủ** — Phần Hero với logo trang và một câu trích dẫn đặc trưng
- **Điều hướng động có hiệu ứng** — Một chú "thỏ" CSS tùy chỉnh xuất hiện trên mục điều hướng đang hoạt động với hiệu ứng tai lơ lửng
- **Danh sách blog** — Lưới thẻ bài viết đáp ứng 2 cột (1 cột trên thiết bị di động)
- **Phân trang lười** — 8 bài viết mỗi trang; chỉ các tệp JSON hàng tháng cần thiết mới được tải cho trang hiện tại
- **Tìm kiếm trực tiếp** — Lọc tức thì trên tiêu đề, mô tả và thẻ bài viết (tìm kiếm tất cả các tháng)
- **Trang chi tiết bài viết** — Tác giả, ngày xuất bản/cập nhật, ảnh nổi bật, đoạn mở đầu, các phần nội dung và các thẻ nhãn
- **Giao diện tối hiện đại** — Biến CSS, hiệu ứng chuyển màu (gradient), hiệu ứng phát sáng và hiệu ứng chuyển đổi mượt mà khi di chuột

### 🔐 Bảng quản trị (PHP backend)

- **Xác thực dựa trên phiên làm việc (Session)** — Đăng nhập/đăng xuất bằng thông tin xác thực từ biến môi trường
- **Bảng điều khiển (Dashboard)** — Liệt kê tất cả các bài viết kèm theo thẻ, ngày tháng và các thao tác nhanh (Xem / Sửa / Xóa)
- **Tạo & chỉnh sửa bài viết** — Trình soạn thảo WYSIWYG **TinyMCE** phong phú hỗ trợ hình ảnh, liên kết, mã nguồn, bảng, phương tiện truyền thông và HTML đầy đủ
- **Các phần nội dung động** — Thêm/xóa/đánh số lại nhiều tiêu đề + phần nội dung cho mỗi bài viết
- **Nhập thẻ** — Nhấn Enter hoặc dấu phẩy để thêm thẻ, phím Backspace để xóa, hiển thị dưới dạng các thẻ nhãn (chip)
- **Hình ảnh nổi bật** — Đặt URL hình ảnh tùy chỉnh hoặc để trống để sử dụng hình ảnh giữ chỗ ngẫu nhiên
- **Xác nhận xóa** — Quy trình xóa được bảo vệ với việc hiển thị ID bài viết
- **Lưu trữ JSON hàng tháng** — Các bài viết được tự động tổ chức thành `posts-MM-YYYY.json` (tóm tắt) và `posts-data-MM-YYYY.json` (dữ liệu đầy đủ)
- **Tự động tạo danh mục** — `post-count.js` (`window.POST_COUNT`) được tạo lại sau mỗi lần ghi để phân trang giao diện người dùng tức thì
- **Di chuyển dữ liệu cũ** — Nỗ lực tốt nhất để tự động chuyển đổi từ bố cục tệp đơn lẻ `posts.json` cũ

---

## 📁 Cấu trúc dự án

```
backend/
 ├── assets/
 ├── includes/
 ├── create.php
 ├── edit.php
 └── ...

frontend/
 ├── public/
 ├── src/
 └── data/

docker/
Dockerfile
docker-compose.yml
```

---

## 🚀 Chạy cục bộ

### Yêu cầu tiên quyết

- **Docker** & **Docker Compose** được cài đặt trên máy của bạn

### Các bước thực hiện

1. **Sao chép kho lưu trữ (Clone repository)**

   ```bash
   git clone https://github.com/Tadataka-Rei/Personal_blog.git
   cd Personal_blog
   ```

2. **Tạo tệp môi trường của bạn**

   ```bash
   cp .env.example .env
   ```

   Chỉnh sửa tệp `.env` và thay đổi thông tin xác thực quản trị viên, các cổng (ports), và thêm khóa API TinyMCE của bạn (xem phần [Biến môi trường](#-bien-moi-truong)).

3. **Build và khởi động các container**

   ```bash
   docker compose up -d --build
   ```

4. **Mở trang web**

   | Dịch vụ | URL |
   | ------- | ------------------------------ |
   | Giao diện người dùng | http://localhost:3000/ |
   | Quản trị | http://localhost:8080/backend/ |

5. **Đăng nhập vào bảng quản trị** bằng thông tin xác thực được thiết lập trong tệp `.env` của bạn (mặc định: `admin` / `admin123`).

> [!IMPORTANT]
> Hãy thay đổi thông tin xác thực quản trị mặc định trước khi tiến hành triển khai lên môi trường sản xuất.


### Chạy không cần Docker (tùy chọn)

Yêu cầu **PHP 8.1+** với tiện ích mở rộng `zip` và **nginx** (hoặc bất kỳ máy chủ nào hỗ trợ PHP-FPM).

- Trỏ thư mục gốc web của bạn tới `frontend/public/` cho giao diện người dùng.
- Cấu hình một vị trí PHP-FPM cho thư mục `backend/` (xem `backend/nginx.conf` để biết cấu hình upstream).
- Phục vụ thư mục `frontend/data/` dưới dạng tĩnh (nó chứa các tệp JSON + JS).
- Đảm bảo người dùng máy chủ web có quyền ghi trên `frontend/data/` và `backend/`.
- Thêm vào ../ trước tất cả các đường dẫn trong frontend và backend
---

## 🔧 Biến môi trường

Tất cả cấu hình được đọc từ tệp `.env` (xem `.env.example`). Backend sẽ tự động tải các biến này thông qua `backend/includes/config.php`.

| Biến | Mặc định | Mô tả |
| ------------------ | ------------------------ | ------------------------------------------------------------------ |
| `BACKEND_PORT` | `8080` | Cổng máy chủ ánh xạ tới container backend quản trị (`:80`) |
| `FRONTEND_PORT` | `3000` | Cổng máy chủ ánh xạ tới container giao diện tĩnh (`:80`) |
| `ADMIN_USERNAME` | `admin` | Tên đăng nhập bảng quản trị |
| `ADMIN_PASSWORD` | `admin123` | Mật khẩu đăng nhập bảng quản trị |
| `TINYMCE_API_KEY` | *(trống)* | Khóa API TinyMCE Cloud — nhận một khóa miễn phí tại <https://www.tiny.cloud/> |
| `SITE_URL` | `http://localhost:8080` | URL công khai của bảng quản trị (dùng cho các liên kết trong bảng quản trị) |
| `DATA_DIR` | *(tự động)* | Ghi đè thư mục dữ liệu JSON (mặc định là `frontend/data`) |

### Ghi chú

- `TINYMCE_API_KEY` — để trống để sử dụng phiên bản TinyMCE tự lưu trữ (các tính năng của trình soạn thảo có thể bị giới hạn).
- `DATA_DIR` — trong Docker, giá trị này được đặt thành `/var/www/html/frontend/data` và được gắn kết dưới dạng bind volume để quản trị viên và giao diện người dùng dùng chung một nguồn dữ liệu.

---

## 🎨 Bảng mã màu

Giao diện sử dụng chủ đề tối (dark theme) được điều khiển thông qua các biến tùy chỉnh CSS. Dưới đây là các token chính được sử dụng xuyên suốt giao diện và bảng quản trị.

### Bảng màu cốt lõi

| Token | Giá trị | Sử dụng |
| --------------- | ------------------------- | ------------------------------------------- |
| `--blue` | <span style="display:inline-block;width:40px;height:20px;background:#0935ff;border-radius:4px;"></span> | Xanh dương chính — thanh điều hướng, nút bấm, logo thỏ |
| `--cyan` | <span style="display:inline-block;width:40px;height:20px;background:#00e1ff;border-radius:4px;"></span> | Xanh lơ điểm nhấn — liên kết, đường viền, trạng thái tiêu điểm |
| `--white` | <span style="display:inline-block;width:40px;height:20px;background:#ffffff;border:1px solid #999;border-radius:4px;"></span> | Màu chữ chính |
| `--bg` | <span style="display:inline-block;width:40px;height:20px;background:#070a1a;border-radius:4px;"></span> | Màu nền tối cơ bản |
| `--muted` | <span style="display:inline-block;width:40px;height:20px;background:#ffffffb8;border-radius:4px;"></span> | Chữ phụ (độ mờ 72%) |
| `--muted2` | <span style="display:inline-block;width:40px;height:20px;background:#ffffff8c;border-radius:4px;"></span> | Chữ gợi ý/cấp ba (độ mờ 55%) |
| `--card` | <span style="display:inline-block;width:40px;height:20px;background:#ffffff0f;border-radius:4px;"></span> | Nền thẻ & bảng điều khiển |
| `--border` | <span style="display:inline-block;width:40px;height:20px;background:#00e1ff59;border-radius:4px;"></span> | Đường viền, đường phân cách, hiệu ứng phát sáng |

### Màu trạng thái quản trị

| Token | Giá trị | Sử dụng |
| ------------ | ---------- | ------------------------ |
| `--danger` | <span style="display:inline-block;width:40px;height:20px;background:#ff4757;border-radius:4px;"></span> | Thao tác xóa, lỗi |
| `--success` | <span style="display:inline-block;width:40px;height:20px;background:#2ed573;border-radius:4px;"></span> | Cảnh báo/thông báo thành công |
| `--warning` | <span style="display:inline-block;width:40px;height:20px;background:#ffa502;border-radius:4px;"></span> | Cảnh báo |

### Gradient nền

Phần nền đặc trưng của trang là một dải gradient phân lớp:

```css
background:
  radial-gradient(1200px 500px at 50% -150px, rgba(0,225,255,.22), transparent 60%),
  linear-gradient(180deg, #050717 0%, #070a1a 100%);
```

- **`#050717`** — phần trên của gradient tuyến tính (màu xanh đen gần đen)
- **`#070a1a`** — phần dưới của gradient tuyến tính (`--bg`)
- **`rgba(0,225,255,.22)`** — hiệu ứng phát sáng hình tròn màu xanh lơ ở phía trên

> 💡 Để tùy chỉnh giao diện, hãy cập nhật các biến CSS trong `frontend/src/css/style.css` (các token cơ bản) và các bảng định kiểu cho từng trang (`home.css`, `blog.css`, `post.css`, `about.css`), cùng với tệp `backend/assets/css/admin.css` cho bảng quản trị.

---

## 🚢 Triển khai

### Bản dựng sản xuất (Docker)

1. **Chuẩn bị tệp `.env` của bạn** — thiết lập thông tin xác thực quản trị mạnh, tên miền của bạn làm `SITE_URL`, và khóa `TINYMCE_API_KEY`.

2. **Build và khởi động**

   ```bash
   docker compose up -d --build
   ```

3. **Kiểm tra lại các dịch vụ**

   ```bash
   docker compose ps
   ```

   Cả hai container (`blog-backend`, `blog-frontend`) đều phải hiển thị trạng thái `Up` và hoạt động ổn định.

### Reverse Proxy (tùy chọn)

Đối với việc triển khai công khai, hãy đặt ứng dụng đằng sau một reverse proxy (ví dụ: **Caddy**, **nginx**, hoặc **Traefik**) và trỏ một tên miền tới:

- `http://localhost:8080` → quản trị (`/backend/`)
- `http://localhost:3000` → giao diện người dùng

Cập nhật `SITE_URL` trong tệp `.env` thành URL quản trị công khai (ví dụ: `https://blog.example.com`) và khởi động lại:

```bash
docker compose up -d
```

### Các lưu ý khi triển khai thực tế

- **Thay đổi thông tin xác thực mặc định** — tuyệt đối không giữ nguyên `admin` / `admin123` trên môi trường sản xuất.
- **Thiết lập `TINYMCE_API_KEY`** — bắt buộc để sử dụng đầy đủ các tính năng của trình soạn thảo WYSIWYG.
- **Thiết lập `SITE_URL`** — trỏ về tên miền thực tế của bạn để các liên kết trên bảng quản trị hiển thị chính xác.
- **Xóa các bind mount dùng cho phát triển** — trong `docker-compose.yml`, dịch vụ backend sẽ gắn kết toàn bộ dự án (`.:/var/www/html`) và thư mục `./frontend/data` để phục vụ việc phát triển trực tiếp. Đối với môi trường sản xuất, hãy xóa các mục volume này hoặc thay thế chúng bằng một volume có tên (`blog-data`) để dữ liệu được lưu trữ độc lập với mã nguồn.
- **Sao lưu dữ liệu** — toàn bộ nội dung nằm trong `frontend/data/` (hoặc thư mục `DATA_DIR` của bạn). Hãy sao lưu thường xuyên.

---

## ⭐ Hỗ trợ
<div align="center">

Nếu bạn thấy dự án này hữu ích, hãy cân nhắc tặng nó một ⭐ trên GitHub.

Bạn cũng có thể ủng hộ tôi qua Ko-fi:
[![Ko-fi](https://img.shields.io/badge/Ko--fi-FF5E5B?logo=ko-fi&logoColor=white)](https://ko-fi.com/ratasleepy)
</div>

---

<p align="center">Được tạo ra với rất nhiều cà phê bởi Tevel</p>
