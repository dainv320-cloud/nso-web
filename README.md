# NSO Web Laravel

Backend da được chuyen sang skeleton Laravel 13, dung PostgreSQL va giu giao dien server-rendered hien co chờ trang game mobile: trang chu, tin tức, tai game, nap tien, cam nang va cảnh báo.

## Yeu cau

- PHP 8.3 tro len
- Composer 2.x
- Extension PHP `pdo_pgsql`
- PostgreSQL 14 tro len

## Cau hinh

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Sửa `.env` theo thông tin PostgreSQL tren may:

```text
DB_CONNECTION=pgsql
POSTGRES_DB=nso_web
POSTGRES_USER=nso_user
POSTGRES_PASSWORD=nso_password
POSTGRES_HOST=127.0.0.1
POSTGRES_PORT=5432
```

Tao database va user trong PostgreSQL neu chưa co:

```sql
create user nso_user with password 'nso_password';
create database nso_web owner nso_user;
```

## Chay migration

```bash
php artisan migrate
```

Migration Laravel hien tai goi lai cac file SQL trong `migrations/` de giu schema va seed data cu.

## Chay server local

```bash
php artisan serve
```

Website chay mac dinh tai `http://127.0.0.1:8000`.

## Route chinh

```text
GET  /
GET  /news
GET  /download
GET  /payment
POST /payment
GET  /profile
GET  /community
GET  /vong-quay
GET  /cam-nang
GET  /cảnh-bao-lua-dao
GET  /api/health
GET  /api/content
GET  /api/users
GET  /api/users/{id}
POST /api/users
```

Tao user mau:

```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Nguyen Van A\",\"email\":\"a@example.com\"}"
```

Migration nam trong `migrations/`.

## Ghi chú chuyen doi

- `public/index.php` da la entrypoint Laravel.
- Route moi nam trong `routes/web.php` va `routes/api.php`.
- View PHP cu van được Laravel doc tu `src/Views` qua `config/view.php`.
- Controller cu trong `src/Controllers` dang được bridge qua route Laravel de giu hảnh vi hien tai. Buoc tiep theo nen tach dan sang controller/service/model Laravel thuan va chuyen form sang CSRF token.
