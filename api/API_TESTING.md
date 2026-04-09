# API Testing Playbook

This document is a fast operational guide for local development, API validation, and smoke testing.

## 1) Start Local Infrastructure

Run from `api/`:

```bash
docker compose up -d database mailer
docker compose ps
```

Check database port:

```bash
docker compose port database 5432
```

If Docker maps PostgreSQL to a random host port (example: `64050`), set it in `.env.local`:

```env
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:64050/app?serverVersion=16&charset=utf8"
MAILER_DSN="smtp://127.0.0.1:64049"
```

## 2) Migrate DB

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## 3) Run API

Встроенный сервер PHP должен идти через `public/router.php` (или иначе в `public/index.php`), иначе маршруты Symfony (`/catalog`, `/design/...`) и часть страниц не заработают (будет 404 от PHP, не от приложения).

```bash
php -S 127.0.0.1:8000 -t public public/router.php
```

Роутер `public/router.php` отдаёт существующие файлы из `public/` напрямую (нужный `Content-Type` для `admin.css` / `admin.js`). Если указать только `public/index.php`, встроенный сервер может отдать `.css` с `text/html`, и стили в админке не применятся.

Проверка HTTP (после старта сервера):

```bash
bash test_http.sh http://127.0.0.1:8000
```

Open docs:

- `http://127.0.0.1:8000/api/docs`

## 4) Admin Auth (JWT)

Dev credentials:

- username: `admin`
- password: `admin123`

Token:

```bash
curl -s -X POST "http://127.0.0.1:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

## 5) Seed Test Data (20 products)

Recommended sequence:

1. Create categories: `merniki`, `ruletki`, `probootborniki`
2. Create products with slugs: `test-product-1` ... `test-product-20`
3. Set article codes: `MRK-1001` ... `PRB-1020`

Note:

- For `price` in Product CRUD, send string values (`"1100.00"`) not numeric literals.

## 6) Core Endpoint Smoke Checklist

### Categories

- `GET /api/categories/tree`
- `GET /api/categories/favorites/main`
- `GET /api/categories/favorites/sidebar`
- `GET /api/categories/by-slug/merniki`
- `GET /api/categories/merniki/products?page=1&limit=5`
- `GET /api/categories/merniki/filters`

### Products / Services

- `GET /api/products/by-slug/test-product-1`
- `GET /api/products/by-articles?articles=MRK-1001,RLT-1006`
- `GET /api/products/popular?limit=5`
- `GET /api/products/new?limit=5`
- `GET /api/services/by-slug/<slug>`
- `GET /api/services?page=1&limit=10`

### Search

- `GET /api/search?q=test&type=products&page=1&limit=10`
- `GET /api/search/autocomplete?q=te&limit=5`

### SEO

- `GET /api/seo/product/test-product-1`
- `GET /api/seo/category/merniki`
- `GET /api/seo/service/<slug>`
- `GET /api/seo/canonical?url=https://merniki.ru/categories/merniki?min_price=1000&type=category`

### Cart

- `GET /api/cart`
- `POST /api/cart/items`
- `PATCH /api/cart/items/{item_id}`
- `DELETE /api/cart/items/{item_id}`
- `DELETE /api/cart`
- `POST /api/cart/checkout`

### Orders

- Public:
  - `POST /api/orders`
  - `GET /api/orders/{order_number}/status`
- Admin:
  - `GET /api/orders` (Bearer token required)
  - `GET /api/orders/{id}`
  - `PATCH /api/orders/{id}/status`
  - `PUT /api/orders/{id}`
  - `DELETE /api/orders/{id}`

## 7) Known Local Caveats

- API Platform custom array responses may appear in Hydra wrapper format (collection-like payload).
- Service/category slug uniqueness errors can return 500 if duplicate insert happens via raw CRUD.
- If Docker was restarted, `database` port can change; update `.env.local` accordingly.

## 8) Quick Health Commands

```bash
php bin/console cache:clear
php bin/console router:match /api/docs --method=GET
php bin/console debug:router --format=txt
```

