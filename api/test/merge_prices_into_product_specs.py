#!/usr/bin/env python3
"""
Подтягивает цены из прайса Excel в design/product-specs-by-category.json.

Команда по умолчанию (прайс из documents/, JSON в design/):

  cd api && python3 test/merge_prices_into_product_specs.py

Явные пути:

  cd api && python3 test/merge_prices_into_product_specs.py \\
    --prices ../documents/прайс\\ Контур-М\\ апрель\\ 2026.xlsx \\
    --json ../design/product-specs-by-category.json

Порядок поиска прайса, если --prices не задан:
  1) documents/прайс Контур-М апрель 2026.xlsx в корне репозитория
  2) любой documents/*прайс*.xlsx
  3) ~/Downloads/*прайс*…*.xlsx
"""
from __future__ import annotations

import argparse
import json
import math
import re
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

import pandas as pd

_SCRIPT_DIR = Path(__file__).resolve().parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))


def _repo_root() -> Path:
    return Path(__file__).resolve().parents[2]


def default_price_list_path(repo: Path) -> Optional[Path]:
    """Прайс в репозитории: documents/прайс Контур-М апрель 2026.xlsx или documents/*прайс*.xlsx"""
    exact = repo / "documents" / "прайс Контур-М апрель 2026.xlsx"
    if exact.is_file():
        return exact
    docs = repo / "documents"
    if docs.is_dir():
        found = sorted(docs.glob("*прайс*.xlsx"))
        if found:
            return found[0]
    return None


def _norm(s: str) -> str:
    s = s.lower().strip()
    s = re.sub(r"\s+", " ", s)
    s = s.replace(",", " ").replace("ё", "е")
    s = re.sub(r"\s+", " ", s)
    return s.strip()


def _num_price(x: Any) -> Optional[float]:
    if x is None or (isinstance(x, float) and math.isnan(x)):
        return None
    if isinstance(x, (int, float)):
        return float(x)
    s = str(x).strip()
    if not s or s.lower() in ("договорная", "nan"):
        return None
    s = re.sub(r"[^\d,.-]", "", s.split("(")[0])
    s = s.replace(",", ".")
    if not s:
        return None
    try:
        return float(s)
    except ValueError:
        return None


def load_m1_prices(path: str) -> Dict[Tuple[str, str], float]:
    """(М1Р-код, секция 01|02|03) -> цена"""
    df = pd.read_excel(path, sheet_name="Мерники 1Р", header=None)
    section = "01"
    out: Dict[Tuple[str, str], float] = {}
    for i in range(len(df)):
        a, b, c = df.iloc[i, 0], df.iloc[i, 1], df.iloc[i, 2]
        t = str(a).strip() if pd.notna(a) else ""
        if "С отметкой" in t and "(01)" in t:
            section = "01"
            continue
        if "переливной" in t and "(02)" in t:
            section = "02"
            continue
        if "шкалой" in t and "(03)" in t:
            section = "03"
            continue
        if pd.isna(b) or str(b).strip() == "nan":
            continue
        code = str(b).strip()
        if not re.match(r"^М1Р-", code):
            continue
        p = _num_price(c)
        if p is None:
            continue
        out[(code, section)] = p
    return out


def m1_section_from_chars(ch: Dict[str, Any]) -> str:
    isp = str(ch.get("Исполнение", "") or "")
    if "(01)" in isp or "01)" in isp:
        return "01"
    if "(02)" in isp:
        return "02"
    if "(03)" in isp:
        return "03"
    return "01"


def norm_m2_code(code: str) -> str:
    s = code.strip().replace("–", "-").replace("—", "-")
    if s.startswith("M2"):
        s = "М2" + s[2:]
    return s


def load_m2_matrix(path: str) -> List[Tuple[str, str, List[Optional[float]]]]:
    """Строки: код, описание, [угл0.1, угл0.05, нерж0.1, нерж0.05] -> cols 3,4,5,7"""
    df = pd.read_excel(path, sheet_name="Мерники", header=None)
    rows: List[Tuple[str, str, List[Optional[float]]]] = []
    for i in range(len(df)):
        code = df.iloc[i, 0]
        desc = df.iloc[i, 1]
        if pd.isna(code) or str(code).strip() in ("nan", ""):
            continue
        code_s = norm_m2_code(str(code).strip())
        if not code_s.startswith("М2Р"):
            continue
        desc_s = str(desc).strip() if pd.notna(desc) else ""
        prices = [
            _num_price(df.iloc[i, 3]),
            _num_price(df.iloc[i, 4]),
            _num_price(df.iloc[i, 5]),
            _num_price(df.iloc[i, 7]),
        ]
        if all(x is None for x in prices):
            continue
        rows.append((code_s, desc_s, prices))
    return rows


def build_m2_desc(orig: str, ch: Dict[str, Any]) -> str:
    pen = str(ch.get("наличие пеногасителя", "") or "").strip().rstrip(",").strip()
    sliv = str(ch.get("тип слива", "") or "")
    spec = str(ch.get("наличие спец шкалы", "") or "")
    if "СШ" in orig:
        return f"пеногаситель, спецшкала, {sliv}".strip()
    if orig.endswith("П"):
        return f"пеногаситель, {sliv}".strip()
    if "М2Р-2000" in orig or "стационарный" in sliv or "передвижной" in sliv:
        # исполнение в тип слива длинной строкой
        return str(ch.get("тип слива", "") or "").strip()
    if pen == "без пеногасителя" and sliv:
        return f"без пеногасителя, {sliv}".strip()
    if pen == "без пеногасителя":
        return "без пеногасителя"
    return ""


def pick_m2_price(
    rows: List[Tuple[str, str, List[Optional[float]]]],
    orig: str,
    ch: Dict[str, Any],
) -> Optional[float]:
    orig_c = norm_m2_code(orig.strip())
    our_desc = build_m2_desc(orig_c, ch)
    our_n = _norm(our_desc)
    steel = str(ch.get("тип стали", "") or "")
    pog = ch.get("погрешность, %")
    try:
        pf = float(pog) if pog is not None else 0.1
    except (TypeError, ValueError):
        pf = 0.1
    if abs(pf - 0.05) < 1e-6:
        sub = 1  # углерод 0.05
    else:
        sub = 0  # 0.1
    if "нержав" in steel.lower():
        col_idx = 2 + sub  # нерж 0.1 / 0.05
    else:
        col_idx = 0 + sub  # углерод

    tier_exact: List[Tuple[int, List[Optional[float]]]] = []
    tier_partial: List[Tuple[int, List[Optional[float]]]] = []
    tier_empty: List[List[Optional[float]]] = []

    for code, ex, prices in rows:
        if code != orig_c:
            continue
        if not (ex and str(ex).strip()):
            tier_empty.append(prices)
            continue
        ex_n = _norm(str(ex))
        if not ex_n:
            tier_empty.append(prices)
            continue
        if our_n == ex_n:
            tier_exact.append((len(ex_n), prices))
        elif our_n.startswith(ex_n) or ex_n in our_n:
            tier_partial.append((len(ex_n), prices))

    chosen: Optional[List[Optional[float]]] = None
    if tier_exact:
        chosen = max(tier_exact, key=lambda x: x[0])[1]
    elif tier_partial:
        chosen = max(tier_partial, key=lambda x: x[0])[1]
    elif tier_empty:
        chosen = tier_empty[0]

    if chosen is None:
        return None
    if col_idx >= len(chosen):
        return None
    return chosen[col_idx]


def load_m1kl_prices(path: str) -> Dict[str, float]:
    df = pd.read_excel(path, sheet_name="Мерники техн", header=None)
    out: Dict[str, float] = {}
    for i in range(len(df)):
        for code_col, price_col in ((1, 2), (5, 7)):
            if code_col >= df.shape[1] or price_col >= df.shape[1]:
                continue
            code = df.iloc[i, code_col]
            if pd.isna(code):
                continue
            c = str(code).strip().replace(" ", "")
            if not c.startswith("М1кл"):
                continue
            p = _num_price(df.iloc[i, price_col])
            if p is not None:
                out[c] = p
    return out


def norm_m1kl(code: str) -> str:
    return re.sub(r"\s+", "", code.strip())


def load_metro_prices(path: str) -> Dict[str, float]:
    df = pd.read_excel(path, sheet_name="Метроштоки", header=None)
    out: Dict[str, float] = {}

    def reg_cell(row: int, col: int) -> Optional[str]:
        if row >= len(df) or col >= df.shape[1]:
            return None
        v = df.iloc[row, col]
        if pd.isna(v):
            return None
        s = str(v).strip()
        return s if s and s != "nan" else None

    blocks = [(0, 1), (3, 4), (6, 7)]
    last_mshs: Dict[int, Optional[str]] = {0: None, 3: None, 6: None}
    for r in range(len(df)):
        for name_col, price_col in blocks:
            label = reg_cell(r, name_col)
            p = _num_price(df.iloc[r, price_col]) if price_col < df.shape[1] else None
            if not label:
                continue
            if label.startswith("МШС"):
                last_mshs[name_col] = label
                if p is None:
                    continue
                out[norm_metro_key(label)] = p
            elif label.startswith("(") and last_mshs.get(name_col):
                prev = last_mshs[name_col]
                if re.match(r"^\(\d+\s*звен", label):
                    merged = re.sub(r"\([^)]*\)", label.strip(), prev, count=1)
                else:
                    merged = f"{prev} {label}".strip()
                if p is not None:
                    out[norm_metro_key(merged)] = p
    return out


def norm_metro_key(s: str) -> str:
    s = re.sub(r"\s+", " ", s.strip())
    s = s.replace("МШС -", "МШС-").replace("МШС-", "МШС-")
    return _norm(s)


def norm_rulet_model(s: str) -> str:
    s = re.sub(r"\s+", "", s.strip())
    s = s.translate(str.maketrans("NH", "НН"))
    s = s.replace("УЗГ", "У3Г").replace("УЗК", "У3К")
    return s


def load_ruletki_prices(path: str) -> Dict[Tuple[str, str], float]:
    """(модель без пробелов, норм вес) -> цена. Два блока: колонки 1–3 и 6–8."""
    df = pd.read_excel(path, sheet_name="Рулетки", header=None)
    out: Dict[Tuple[str, str], float] = {}
    last_model: Dict[int, Optional[str]] = {1: None, 6: None}

    def is_model_cell(s: str) -> bool:
        return bool(re.match(r"^Р\d", s) or re.match(r"^Р100", s))

    for r in range(len(df)):
        for c in (1, 6):
            wcol, pcol = c + 1, c + 2
            if wcol >= df.shape[1]:
                continue
            raw_model = df.iloc[r, c]
            ms = str(raw_model).strip() if pd.notna(raw_model) else ""
            if ms and is_model_cell(ms):
                last_model[c] = ms
            elif not ms and last_model.get(c):
                ms = last_model[c]
            else:
                ms = last_model.get(c) or ""
            if not ms or not is_model_cell(ms):
                continue
            wt = df.iloc[r, wcol]
            pr = df.iloc[r, pcol] if pcol < df.shape[1] else None
            p = _num_price(pr)
            if p is None:
                continue
            mkey = norm_rulet_model(ms)
            wkey = _norm_weight(str(wt) if pd.notna(wt) else "")
            out[(mkey, wkey)] = p
    return out


def _norm_weight(w: str) -> str:
    w = w.lower().replace(" ", "")
    if w in ("", "nan"):
        return ""
    return w


def parse_ruletka_name(name: str) -> Optional[Tuple[str, str]]:
    s = name.replace("Рулетка", "").strip()
    wm = re.search(r"([\d,\.]+\s*кг)\s*$", s, re.I)
    if wm:
        wkey = _norm_weight(wm.group(1))
        rest = s[: wm.start()].strip()
    else:
        wkey = ""
        rest = s
    if not rest:
        return None
    model = norm_rulet_model(rest)
    return (model, wkey)


def rulet_lookup_rule(
    rulet: Dict[Tuple[str, str], float], model: str, wkey: str
) -> Optional[float]:
    model = norm_rulet_model(model)
    if (model, wkey) in rulet:
        return rulet[(model, wkey)]
    if wkey and (model, "") in rulet:
        return rulet[(model, "")]
    best: Optional[float] = None
    for (m, w), pv in rulet.items():
        if m != model:
            continue
        if wkey and w and (wkey == w or wkey in w or w in wkey):
            return pv
        if not wkey or not w:
            best = pv
    return best


def merge_prices(
    data: Dict[str, Any],
    price_path: str,
) -> Tuple[int, int, List[str]]:
    m1 = load_m1_prices(price_path)
    m2rows = load_m2_matrix(price_path)
    m1kl = load_m1kl_prices(price_path)
    metro = load_metro_prices(price_path)
    rulet = load_ruletki_prices(price_path)

    matched = 0
    missing: List[str] = []

    for cat in data.get("categories", []):
        cname = cat.get("category") or ""
        for prod in cat.get("products") or []:
            prod.pop("price_rub", None)
            orig = str(prod.get("original_name") or prod.get("name") or "").strip()
            ch = prod.get("characteristics") or {}
            if not isinstance(ch, dict):
                ch = {}

            price: Optional[float] = None

            if orig.startswith("М1Р-") and "Мерники эталонные 1" in cname:
                sec = m1_section_from_chars(ch)
                price = m1.get((orig, sec))
                if price is None:
                    for k, v in m1.items():
                        if k[0] == orig:
                            price = v
                            break

            elif orig.startswith("М2Р"):
                price = pick_m2_price(m2rows, orig, ch)

            elif orig.startswith("М1кл") or "М1кл" in orig:
                nk = norm_m1kl(orig)
                price = m1kl.get(nk)

            elif orig.startswith("МШС"):
                k = norm_metro_key(orig)
                price = metro.get(k)
                if price is None:
                    for mk, mv in metro.items():
                        if mk in k or k in mk:
                            price = mv
                            break

            elif orig.startswith("Рулетка"):
                pr = parse_ruletka_name(orig)
                if pr:
                    model, wk = pr
                    price = rulet_lookup_rule(rulet, model, wk)

            if price is not None:
                prod["price_rub"] = int(round(price))
                matched += 1
            else:
                missing.append(f"{cname} / {orig}")

    return matched, len(missing), missing


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument(
        "--prices",
        type=Path,
        default=None,
        help="Путь к прайсу xlsx",
    )
    ap.add_argument(
        "--json",
        type=Path,
        default=None,
        help="Путь к product-specs-by-category.json",
    )
    ap.add_argument(
        "--report-missing",
        type=int,
        default=40,
        help="Сколько несопоставленных позиций вывести (0 = все)",
    )
    args = ap.parse_args()

    repo = _repo_root()
    price_path = args.prices
    if price_path is None:
        price_path = default_price_list_path(repo)
        if price_path is None:
            d = Path.home() / "Downloads"
            candidates = list(d.glob("*прайс*Контур*2026*.xlsx")) + list(d.glob("*прайс*.xlsx"))
            if not candidates:
                print(
                    "Прайс не найден: положите xlsx в documents/ или укажите --prices",
                    file=sys.stderr,
                )
                sys.exit(1)
            price_path = candidates[0]

    json_path = args.json or (repo / "design" / "product-specs-by-category.json")
    if not json_path.is_file():
        print(f"Нет файла {json_path}", file=sys.stderr)
        sys.exit(1)

    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)

    data["price_source_file"] = price_path.name

    matched, nmiss, missing = merge_prices(data, str(price_path))

    with open(json_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

    print(json.dumps({"matched": matched, "unmatched": nmiss, "price_file": str(price_path)}, ensure_ascii=False))
    lim = args.report_missing
    if missing and lim != 0:
        show = missing if lim < 0 else missing[: lim]
        print("--- не сопоставлено с прайсом ---")
        for line in show:
            print(line)
        if len(missing) > len(show):
            print(f"... и ещё {len(missing) - len(show)}")


if __name__ == "__main__":
    main()
