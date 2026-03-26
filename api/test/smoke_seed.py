#!/usr/bin/env python3
import json
import time
from typing import Dict, List

from smoke_common import env_client


def ensure_categories(client) -> Dict[str, str]:
    expected = [
        ("merniki", "Мерники"),
        ("ruletki", "Рулетки"),
        ("probootborniki", "Пробоотборники"),
    ]
    status, payload = client.call("GET", "/api/categories")
    if status != 200:
        raise RuntimeError(f"GET /api/categories failed: {status} {payload}")

    existing = {
        item.get("slug"): item.get("id")
        for item in payload.get("hydra:member", [])
        if isinstance(item, dict)
    }

    for slug, name in expected:
        if slug in existing and existing[slug]:
            continue
        status, body = client.call(
            "POST",
            "/api/categories",
            {"name": name, "slug": slug, "description": f"Категория {name}"},
            content_type="application/ld+json",
        )
        if status not in (200, 201):
            raise RuntimeError(f"POST /api/categories {slug} failed: {status} {body}")
        existing[slug] = body.get("id")

    return {slug: existing[slug] for slug, _ in expected}


def ensure_category_tree(client) -> Dict[str, str]:
    tree = [
        ("izmeritelnoe-oborudovanie", "Измерительное оборудование", None),
        ("geodezicheskoe-oborudovanie", "Геодезическое оборудование", None),
        ("laboratornoe-oborudovanie", "Лабораторное оборудование", None),
        ("merniki-obrazcovye", "Мерники образцовые", "izmeritelnoe-oborudovanie"),
        ("ruletki-metallicheskie", "Рулетки металлические", "izmeritelnoe-oborudovanie"),
        ("probootborniki-nefteproduktov", "Пробоотборники нефтепродуктов", "laboratornoe-oborudovanie"),
        ("niveliry", "Нивелиры", "geodezicheskoe-oborudovanie"),
        ("teodolity", "Теодолиты", "geodezicheskoe-oborudovanie"),
        ("termometry-lab", "Термометры лабораторные", "laboratornoe-oborudovanie"),
        ("manometry-lab", "Манометры лабораторные", "laboratornoe-oborudovanie"),
    ]

    status, payload = client.call("GET", "/api/categories")
    if status != 200:
        raise RuntimeError(f"GET /api/categories failed: {status} {payload}")

    existing = {
        item.get("slug"): item.get("id")
        for item in payload.get("hydra:member", [])
        if isinstance(item, dict)
    }

    created = 0
    changed = True
    while changed:
        changed = False
        for slug, name, parent_slug in tree:
            if slug in existing and existing[slug]:
                continue
            if parent_slug and parent_slug not in existing:
                continue

            body = {
                "name": name,
                "slug": slug,
                "description": f"Категория {name}",
                "parent_id": existing.get(parent_slug),
                "display_mode": "products_only",
                "aggregate_products": True,
            }
            status, resp = client.call(
                "POST",
                "/api/categories",
                body,
                content_type="application/ld+json",
            )
            if status not in (200, 201):
                raise RuntimeError(f"POST /api/categories {slug} failed: {status} {resp}")
            existing[slug] = resp.get("id")
            created += 1
            changed = True

    missing = [slug for slug, _, _ in tree if slug not in existing]
    if missing:
        raise RuntimeError(f"Failed to create category tree nodes: {missing}")

    result = {slug: existing[slug] for slug, _, _ in tree}
    result["_created"] = str(created)
    return result


def create_test_products(client, category_ids: Dict[str, str], total_products: int = 20) -> int:
    created = 0
    for i in range(1, total_products + 1):
        if i <= 7:
            category_slug, prefix = "merniki", "MRK"
        elif i <= 14:
            category_slug, prefix = "ruletki", "RLT"
        else:
            category_slug, prefix = "probootborniki", "PRB"

        payload = {
            "category_id": category_ids[category_slug],
            "name": f"Тестовый товар {i}",
            "slug": f"test-product-{i}",
            "article": f"{prefix}-{1000+i}",
            "description": f"Описание тестового товара {i}",
            "technical_specs": {
                "volume": [f"{i}л"],
                "material": ["нержавеющая сталь" if i % 2 else "алюминий"],
                "diameter": [f"{50+i}мм"],
                "has_verification": bool(i % 2),
            },
            "price": f"{1000+i*100:.2f}",
            "stock_status": "in_stock" if i % 2 else "on_order",
            "manufacturing_time": "5-7 дней",
            "gost_number": f"ГОСТ-{i}",
            "has_verification": bool(i % 2),
            "drawings": [f"/uploads/drawings/{i}.pdf"],
            "documents": [f"/uploads/docs/{i}.pdf"],
            "certificates": [f"/uploads/certs/{i}.pdf"],
        }
        status, _ = client.call(
            "POST", "/api/products", payload, content_type="application/ld+json"
        )
        if status in (200, 201):
            created += 1
    return created


def ensure_sample_catalog_tree(client) -> Dict[str, str]:
    """Демо-каталог из PROMPT/витрины: 3 корня, подкатегории, товары с тех. характеристиками."""
    tree = [
        ("sample-izmeritelnoe-oborudovanie", "Измерительное оборудование", None, 1, "subcategories_only"),
        ("sample-izmeritelnye-instrumenty", "Измерительные инструменты", None, 2, "subcategories_only"),
        ("sample-poverochnye-komplekty", "Поверочные комплекты", None, 3, "subcategories_only"),
        ("sample-merniki", "Мерники", "sample-izmeritelnoe-oborudovanie", 1, "products_only"),
        ("sample-metroshchoki", "Метроштоки", "sample-izmeritelnoe-oborudovanie", 2, "products_only"),
        (
            "sample-ruletki-i-lenty",
            "Рулетки и ленты",
            "sample-izmeritelnye-instrumenty",
            1,
            "products_only",
        ),
        (
            "sample-probootborniki-instr",
            "Пробоотборники",
            "sample-izmeritelnye-instrumenty",
            2,
            "products_only",
        ),
        (
            "sample-komplekty-mernyh-gruzov",
            "Комплекты мерных грузов",
            "sample-poverochnye-komplekty",
            1,
            "products_only",
        ),
        (
            "sample-poverochnye-zhidkosti",
            "Поверочные жидкости",
            "sample-poverochnye-komplekty",
            2,
            "products_only",
        ),
    ]

    status, payload = client.call("GET", "/api/categories")
    if status != 200:
        raise RuntimeError(f"GET /api/categories failed: {status} {payload}")

    existing = {
        item.get("slug"): item.get("id")
        for item in payload.get("hydra:member", [])
        if isinstance(item, dict)
    }

    created = 0
    changed = True
    while changed:
        changed = False
        for slug, name, parent_slug, sort_order, display_mode in tree:
            if slug in existing and existing[slug]:
                continue
            if parent_slug and parent_slug not in existing:
                continue

            body = {
                "name": name,
                "slug": slug,
                "description": f"Тестовая выборка: {name}",
                "parent_id": existing.get(parent_slug),
                "sort_order": sort_order,
                "display_mode": display_mode,
                "aggregate_products": False,
            }
            status, resp = client.call(
                "POST",
                "/api/categories",
                body,
                content_type="application/ld+json",
            )
            if status not in (200, 201):
                raise RuntimeError(f"POST /api/categories {slug} failed: {status} {resp}")
            existing[slug] = resp.get("id")
            created += 1
            changed = True

    missing = [row[0] for row in tree if row[0] not in existing]
    if missing:
        raise RuntimeError(f"Failed to create sample catalog categories: {missing}")

    result = {row[0]: existing[row[0]] for row in tree}
    result["_created_categories"] = str(created)
    return result


def _specs(rows: Dict[str, str]) -> Dict[str, List[str]]:
    return {k: [v] for k, v in rows.items()}


def create_sample_catalog_products(client, category_ids: Dict[str, str]) -> int:
    items = [
        (
            "sample-merniki",
            {
                "name": "Мерник шкальный М1кл 100‑1.1",
                "slug": "sample-mernik-shkalnyj-m1kl-100-1-1",
                "article": "DEMO-MRK-001",
                "description": "Мерник шкального типа, 1 класс точности.",
                "technical_specs": _specs(
                    {
                        "Объём": "100 л",
                        "Материал": "нержавеющая сталь",
                        "Тип шкалы": "клёпаная",
                        "Поверка": "предусмотрена",
                    }
                ),
                "has_verification": True,
            },
        ),
        (
            "sample-merniki",
            {
                "name": "Мерник с пеногасителем, нижний слив 50 л",
                "slug": "sample-mernik-penogasitel-nizhnij-sliv-50l",
                "article": "DEMO-MRK-002",
                "description": "Мерник с пеногасителем, слив снизу.",
                "technical_specs": _specs(
                    {
                        "Объём": "50 л",
                        "Материал": "нержавеющая сталь",
                        "Пеногаситель": "есть",
                        "Конфигурация": "нижний слив",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-merniki",
            {
                "name": "Мерник стационарный 200 л, 2 разряда",
                "slug": "sample-mernik-statsionarnyj-200l-2-razrjad",
                "article": "DEMO-MRK-003",
                "description": "Стационарный мерник повышенной вместимости.",
                "technical_specs": _specs(
                    {
                        "Объём": "200 л",
                        "Материал": "нержавеющая сталь",
                        "Крепёж": "тумба + домкраты",
                        "Поверка": "по ГОСТ",
                    }
                ),
                "has_verification": True,
            },
        ),
        (
            "sample-metroshchoki",
            {
                "name": "Метрошток МШС‑3,5 круглый анодированный",
                "slug": "sample-metroshchok-mhs-3-5-kruglyj-anod",
                "article": "DEMO-MSH-001",
                "description": "Круглый метрошток с анодированием.",
                "technical_specs": _specs(
                    {
                        "Длина": "3,5 м",
                        "Материал": "сталь с анодированием",
                        "Форма": "круглая",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-metroshchoki",
            {
                "name": "Метрошток МШС‑4,5 телескопический, 3 звена",
                "slug": "sample-metroshchok-mhs-4-5-teleskop-3-zvena",
                "article": "DEMO-MSH-002",
                "description": "Телескопическая конструкция из трёх звеньев.",
                "technical_specs": _specs(
                    {
                        "Длина": "4,5 м",
                        "Конструкция": "телескопическая",
                        "Материал": "сталь с покрытием",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-metroshchoki",
            {
                "name": "Метрошток МШС‑6,0 усиленный для АЗС",
                "slug": "sample-metroshchok-mhs-6-azs",
                "article": "DEMO-MSH-003",
                "description": "Усиленный метрошток для резервуаров и АЗС.",
                "technical_specs": _specs(
                    {
                        "Длина": "6 м",
                        "Материал": "усиленная сталь",
                        "Применение": "измерение уровней топлива",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-ruletki-i-lenty",
            {
                "name": "Рулетка Р20Н2Г с лотом 1 кг",
                "slug": "sample-ruletka-r20n2g-lot-1kg",
                "article": "DEMO-RLT-001",
                "description": "Рулетка с грузом-лотом.",
                "technical_specs": _specs(
                    {
                        "Длина ленты": "20 м",
                        "Нагрузка лота": "1 кг",
                        "Класс точности": "по поверке",
                    }
                ),
                "has_verification": True,
            },
        ),
        (
            "sample-ruletki-i-lenty",
            {
                "name": "Рулетка Р10Н2Г с лотом 2 кг",
                "slug": "sample-ruletka-r10n2g-lot-2kg",
                "article": "DEMO-RLT-002",
                "description": "Рулетка с увеличенным лотом.",
                "technical_specs": _specs(
                    {
                        "Длина ленты": "10 м",
                        "Нагрузка лота": "2 кг",
                        "Поверка": "выполнена",
                    }
                ),
                "has_verification": True,
            },
        ),
        (
            "sample-ruletki-i-lenty",
            {
                "name": "Лента измерительная 50 м, стальная",
                "slug": "sample-lenta-50m-stalnaya",
                "article": "DEMO-RLT-003",
                "description": "Стальная измерительная лента для наружных работ.",
                "technical_specs": _specs(
                    {
                        "Длина": "50 м",
                        "Материал": "сталь",
                        "Применение": "наружные измерения",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-probootborniki-instr",
            {
                "name": "Пробоотборник ПО‑80",
                "slug": "sample-po-80",
                "article": "DEMO-PO-001",
                "description": "Пробоотборник диаметром 80 мм.",
                "technical_specs": _specs(
                    {
                        "Объём": "1 л",
                        "Диаметр": "80 мм",
                        "Материал": "металл",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-probootborniki-instr",
            {
                "name": "Пробоотборник ПО‑45‑500",
                "slug": "sample-po-45-500",
                "article": "DEMO-PO-002",
                "description": "Для отбора проб нефтепродуктов.",
                "technical_specs": _specs(
                    {
                        "Объём": "500 мл",
                        "Диаметр": "45 мм",
                        "Применение": "нефтепродукты",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-probootborniki-instr",
            {
                "name": "Пробоотборник ПО‑100‑2 л",
                "slug": "sample-po-100-2l",
                "article": "DEMO-PO-003",
                "description": "Из нержавеющей стали для агрессивных сред.",
                "technical_specs": _specs(
                    {
                        "Объём": "2 л",
                        "Материал": "нержавеющая сталь",
                        "Применение": "химические жидкости",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-probootborniki-instr",
            {
                "name": "Пробоотборник ПО‑50 для агрессивных сред",
                "slug": "sample-po-50-agressiv",
                "article": "DEMO-PO-004",
                "description": "Химически стойкое исполнение.",
                "technical_specs": _specs(
                    {
                        "Объём": "500 мл",
                        "Материал": "химически стойкая сталь",
                        "Применение": "кислоты, щёлочи",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-komplekty-mernyh-gruzov",
            {
                "name": "Комплект МГ‑1, 10 кг",
                "slug": "sample-mg-1-10kg",
                "article": "DEMO-MG-001",
                "description": "Мерные грузы для поверки.",
                "technical_specs": _specs(
                    {
                        "Вес": "10 кг",
                        "Материал": "сталь",
                        "Применение": "поверка рулеток и весов",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-komplekty-mernyh-gruzov",
            {
                "name": "Комплект МГ‑2, 5 кг",
                "slug": "sample-mg-2-5kg",
                "article": "DEMO-MG-002",
                "description": "Чугунный комплект для лабораторий.",
                "technical_specs": _specs(
                    {
                        "Вес": "5 кг",
                        "Материал": "чугун",
                        "Применение": "лабораторная поверка",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-komplekty-mernyh-gruzov",
            {
                "name": "Комплект МГ‑3, 20 кг",
                "slug": "sample-mg-3-20kg",
                "article": "DEMO-MG-003",
                "description": "Промышленный набор с калибровкой.",
                "technical_specs": _specs(
                    {
                        "Вес": "20 кг",
                        "Материал": "сталь с калибровкой",
                        "Применение": "промышленная поверка",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-poverochnye-zhidkosti",
            {
                "name": "Жидкость эталонная для мерников 1 л",
                "slug": "sample-zhidkost-etalonnaya-merniki-1l",
                "article": "DEMO-ZHD-001",
                "description": "Сертифицированная эталонная жидкость.",
                "technical_specs": _specs(
                    {
                        "Объём": "1 л",
                        "Состав": "сертифицированная смесь",
                        "Температурный диапазон": "-10…+50 °C",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-poverochnye-zhidkosti",
            {
                "name": "Жидкость эталонная для мерников 5 л",
                "slug": "sample-zhidkost-etalonnaya-merniki-5l",
                "article": "DEMO-ZHD-002",
                "description": "Увеличенная фасовка эталонной смеси.",
                "technical_specs": _specs(
                    {
                        "Объём": "5 л",
                        "Состав": "сертифицированная смесь",
                        "Температурный диапазон": "-10…+50 °C",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-poverochnye-zhidkosti",
            {
                "name": "Жидкость поверочная для нефтепродуктов 2 л",
                "slug": "sample-zhidkost-poverochnaya-nefteprodukty-2l",
                "article": "DEMO-ZHD-003",
                "description": "Для поверки на АЗС и НПЗ.",
                "technical_specs": _specs(
                    {
                        "Объём": "2 л",
                        "Состав": "нефтяной эталон",
                        "Применение": "АЗС и НПЗ",
                    }
                ),
                "has_verification": False,
            },
        ),
        (
            "sample-poverochnye-zhidkosti",
            {
                "name": "Жидкость эталонная для лабораторной поверки 0,5 л",
                "slug": "sample-zhidkost-etalonnaya-lab-0-5l",
                "article": "DEMO-ZHD-004",
                "description": "Лабораторная фасовка.",
                "technical_specs": _specs(
                    {
                        "Объём": "0,5 л",
                        "Состав": "сертифицированный эталон",
                        "Температурный диапазон": "-5…+40 °C",
                    }
                ),
                "has_verification": False,
            },
        ),
    ]

    status, payload = client.call("GET", "/api/products")
    if status != 200:
        raise RuntimeError(f"GET /api/products failed: {status} {payload}")
    existing_slugs = {
        item.get("slug")
        for item in payload.get("hydra:member", [])
        if isinstance(item, dict) and item.get("slug")
    }

    created = 0
    for cat_slug, prod in items:
        slug = prod["slug"]
        if slug in existing_slugs:
            continue
        category_id = category_ids.get(cat_slug)
        if not category_id:
            continue
        payload_body = {
            "category_id": category_id,
            "name": prod["name"],
            "slug": slug,
            "article": prod["article"],
            "description": prod["description"],
            "technical_specs": prod["technical_specs"],
            "price": None,
            "stock_status": "on_order",
            "manufacturing_time": None,
            "has_verification": prod["has_verification"],
        }
        status, _ = client.call(
            "POST",
            "/api/products",
            payload_body,
            content_type="application/ld+json",
        )
        if status in (200, 201):
            created += 1
            existing_slugs.add(slug)
    return created


def create_tree_products(client, category_ids: Dict[str, str], per_category: int = 4) -> int:
    leaf_map = [
        ("merniki-obrazcovye", "MOB", "Мерник образцовый"),
        ("ruletki-metallicheskie", "RLM", "Рулетка металлическая"),
        ("probootborniki-nefteproduktov", "PRN", "Пробоотборник"),
        ("niveliry", "NVL", "Нивелир"),
        ("teodolity", "TDL", "Теодолит"),
        ("termometry-lab", "TRL", "Термометр"),
        ("manometry-lab", "MNL", "Манометр"),
    ]

    status, payload = client.call("GET", "/api/products")
    if status != 200:
        raise RuntimeError(f"GET /api/products failed: {status} {payload}")
    existing_slugs = {
        item.get("slug")
        for item in payload.get("hydra:member", [])
        if isinstance(item, dict) and item.get("slug")
    }

    created = 0
    idx = 1
    for cat_slug, prefix, title in leaf_map:
        category_id = category_ids.get(cat_slug)
        if not category_id:
            continue
        for i in range(1, per_category + 1):
            slug = f"tree-{cat_slug}-{i}"
            if slug in existing_slugs:
                idx += 1
                continue
            payload = {
                "category_id": category_id,
                "name": f"{title} {i}",
                "slug": slug,
                "article": f"{prefix}-{2000+idx}",
                "description": f"Тестовый товар для ветки {cat_slug}",
                "technical_specs": {
                    "material": ["сталь" if idx % 2 else "алюминий"],
                    "class": [f"{1 + (idx % 3)}"],
                    "range": [f"{10 + idx} ед."],
                },
                "price": f"{2500 + idx * 150:.2f}",
                "stock_status": "in_stock" if idx % 2 else "on_order",
                "manufacturing_time": "3-5 дней",
                "gost_number": f"ГОСТ-TREE-{idx}",
                "has_verification": bool(idx % 2),
                "drawings": [f"/uploads/drawings/tree-{idx}.pdf"],
                "documents": [f"/uploads/docs/tree-{idx}.pdf"],
                "certificates": [f"/uploads/certs/tree-{idx}.pdf"],
            }
            status, _ = client.call(
                "POST",
                "/api/products",
                payload,
                content_type="application/ld+json",
            )
            if status in (200, 201):
                created += 1
                existing_slugs.add(slug)
            idx += 1
    return created


def create_service_for_tests(client) -> str:
    slug = f"service-smoke-{int(time.time())}"
    status, payload = client.call(
        "POST",
        "/api/services",
        {
            "name": "Smoke Service",
            "slug": slug,
            "description": "Service for smoke tests",
            "price": "3500.00",
            "price_type": "from",
            "requires_technical_spec": False,
        },
        content_type="application/ld+json",
    )
    if status not in (200, 201):
        raise RuntimeError(f"POST /api/services failed: {status} {payload}")
    return slug


def main() -> None:
    client = env_client()
    client.login()
    category_ids = ensure_categories(client)
    tree_category_ids = ensure_category_tree(client)
    sample_tree_ids = ensure_sample_catalog_tree(client)
    created_products = create_test_products(client, category_ids)
    tree_products = create_tree_products(client, tree_category_ids)
    sample_products = create_sample_catalog_products(client, sample_tree_ids)
    service_slug = create_service_for_tests(client)
    status, payload = client.call("GET", "/api/products")
    total = payload.get("hydra:totalItems", None) if status == 200 else None
    print(
        json.dumps(
            {
                "categories": category_ids,
                "tree_categories": tree_category_ids,
                "sample_catalog_categories": sample_tree_ids,
                "created_products": created_products,
                "created_tree_products": tree_products,
                "created_sample_catalog_products": sample_products,
                "service_slug": service_slug,
                "products_total": total,
            },
            ensure_ascii=False,
        )
    )


if __name__ == "__main__":
    main()

