#!/usr/bin/env python3
import json
import time
from typing import List, Tuple

from smoke_common import env_client


def main() -> None:
    client = env_client()
    client.login()

    checks: List[Tuple[str, int]] = []

    # prepare unique service for by-slug and SEO checks
    service_slug = f"service-smoke-run-{int(time.time())}"
    status, _ = client.call(
        "POST",
        "/api/services",
        {
            "name": "Smoke Run Service",
            "slug": service_slug,
            "description": "Service for smoke run",
            "price": "4200.00",
            "price_type": "from",
            "requires_technical_spec": False,
        },
        content_type="application/ld+json",
    )
    checks.append(("POST /api/services", status))

    # public endpoints
    public_paths = [
        "/api/categories/tree",
        "/api/categories/favorites",
        "/api/categories/favorites/main",
        "/api/categories/favorites/sidebar",
        "/api/categories/by-slug/merniki",
        "/api/categories/merniki/products?page=1&limit=5",
        "/api/categories/merniki/filters",
        "/api/products/by-slug/test-product-1",
        "/api/products/by-articles?articles=MRK-1001,RLT-1006",
        "/api/products/popular?limit=5",
        "/api/products/new?limit=5",
        f"/api/services/by-slug/{service_slug}",
        "/api/services?page=1&limit=10",
        "/api/search?q=test&type=products&page=1&limit=10",
        "/api/search/autocomplete?q=te&limit=5",
        "/api/seo/product/test-product-1",
        "/api/seo/category/merniki",
        f"/api/seo/service/{service_slug}",
        "/api/seo/canonical?url=https://merniki.ru/categories/merniki?min_price=1000&type=category",
        "/api/cart",
    ]
    for path in public_paths:
        status, _ = client.call("GET", path)
        checks.append((f"GET {path}", status))

    # fetch one product id for cart/order flow
    status, products = client.call("GET", "/api/products?page=1&itemsPerPage=2")
    checks.append(("GET /api/products?page=1&itemsPerPage=2", status))
    product_id = None
    if status == 200:
        members = products.get("hydra:member", [])
        if members and isinstance(members[0], dict):
            product_id = members[0].get("id")

    if not product_id:
        raise RuntimeError("Cannot determine product id for cart/orders tests.")

    # cart flow
    status, _ = client.call("DELETE", "/api/cart")
    checks.append(("DELETE /api/cart", status))
    status, _ = client.call(
        "POST",
        "/api/cart/items",
        {"type": "product", "id": product_id, "quantity": 1},
    )
    checks.append(("POST /api/cart/items", status))
    status, checkout_payload = client.call(
        "POST",
        "/api/cart/checkout",
        {
            "customer_name": "Ivan Smoke",
            "customer_company": "OOO Smoke",
            "customer_phone": "+79991234567",
            "customer_email": "ivan-smoke@example.com",
            "comment": "smoke checkout",
            "attachments": ["/uploads/smoke.pdf"],
        },
    )
    checks.append(("POST /api/cart/checkout", status))

    # direct order flow
    status, order_payload = client.call(
        "POST",
        "/api/orders",
        {
            "customer_name": "Petr Smoke",
            "customer_company": "OOO Smoke",
            "customer_phone": "+79990001122",
            "customer_email": "petr-smoke@example.com",
            "items": [
                {
                    "type": "product",
                    "id": product_id,
                    "name": "Smoke Product",
                    "article": "MRK-1001",
                    "quantity": 1,
                    "price": 1100,
                }
            ],
            "comment": "smoke direct order",
            "attachments": ["/uploads/direct-smoke.pdf"],
        },
    )
    checks.append(("POST /api/orders", status))

    order_number = None
    if status in (200, 201):
        members = order_payload.get("hydra:member", [])
        if isinstance(members, list) and members:
            order_number = members[0]

    if order_number:
        status, _ = client.call("GET", f"/api/orders/{order_number}/status")
        checks.append(("GET /api/orders/{order_number}/status", status))

    # admin endpoint
    status, _ = client.call("GET", "/api/orders")
    checks.append(("GET /api/orders (admin)", status))

    failed = [(name, code) for (name, code) in checks if not (200 <= code < 300)]
    summary = {
        "checked": len(checks),
        "passed": len(checks) - len(failed),
        "failed": [{"endpoint": n, "status": c} for n, c in failed],
    }
    print(json.dumps(summary, ensure_ascii=False))

    if failed:
        raise SystemExit(1)


if __name__ == "__main__":
    main()

