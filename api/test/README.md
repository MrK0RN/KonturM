# Test Scripts

This folder contains smoke scripts for full API system checks.

## Files

- `smoke_common.py` - shared HTTP client and auth helper.
- `smoke_seed.py` - creates baseline data (categories, 20 products, service).
- `smoke_run.py` - runs full smoke checks on public/admin/cart/orders/seo/search endpoints.
- `run_all.sh` - seed + smoke run in one command.

## Prerequisites

1. API is running on `http://127.0.0.1:8000` (or set `API_BASE_URL`).
2. Database is migrated and reachable.
3. Admin credentials are valid.

Environment overrides:

- `API_BASE_URL` (default `http://127.0.0.1:8000`)
- `API_ADMIN_USER` (default `admin`)
- `API_ADMIN_PASSWORD` (default `admin123`)

## Usage

Run all:

```bash
cd api/test
bash run_all.sh
```

Run separately:

```bash
cd api/test
python3 smoke_seed.py
python3 smoke_run.py
```

Output is JSON summary with passed/failed checks.

