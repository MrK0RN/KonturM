/**
 * Admin UI for KonturM API — same-origin: /admin/ → /api/
 */

const STORAGE_KEY = "konturm_admin_jwt";
const API_BASE = "/api";

/** Коллекции API Platform: при application/json часто приходит «голый» массив без totalItems — ломается пагинация в админке */
const API_ACCEPT_JSONLD = { Accept: "application/ld+json" };

const state = {
  token: localStorage.getItem(STORAGE_KEY) || "",
  section: "login",
  collectionUrl: "",
  nextPageUrl: null,
  editing: null,
  /** Пагинация / фильтры списка товаров в админке */
  productsList: {
    page: 1,
    itemsPerPage: 25,
    name: "",
    article: "",
    slug: "",
    categoryId: "",
    stockStatus: "",
    orderField: "name",
    orderDir: "asc",
  },
  priceListEditor: {
    page: 1,
    itemsPerPage: 50,
    total: 0,
    query: "",
    /** @type {Record<string, string>} */
    dirtyPrices: {},
  },
};

function authHeaders(withAuth = true) {
  const h = {
    Accept: "application/json",
    "Content-Type": "application/json",
  };
  if (withAuth && state.token) {
    h.Authorization = `Bearer ${state.token}`;
  }
  return h;
}

/** Сообщение для формы входа при 401 на защищённых запросах (не логин). */
function humanizeJwt401Message(data) {
  const raw =
    typeof data === "object" && data !== null
      ? String(data.message ?? data.detail ?? data.title ?? "")
      : String(data ?? "");
  const r = raw.toLowerCase();
  if (r.includes("expired")) {
    return "Срок действия входа истёк. Войдите снова.";
  }
  if (r.includes("invalid") && r.includes("jwt")) {
    return "Сессия недействительна. Войдите снова.";
  }
  return "Требуется повторный вход.";
}

function sessionExpiredToLogin(message) {
  state.orderDetailId = null;
  setToken("");
  const modal = document.getElementById("modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.setAttribute("aria-hidden", "true");
  }
  const loginErr = document.getElementById("login-error");
  if (loginErr) {
    loginErr.textContent = message;
    loginErr.classList.remove("hidden");
  }
  goSection("login");
}

async function apiFetch(path, options = {}) {
  const { skipAuth = false, ...rest } = options;
  const url = path.startsWith("http") ? path : `${API_BASE}${path}`;
  const res = await fetch(url, {
    ...rest,
    headers: { ...authHeaders(!skipAuth), ...rest.headers },
    credentials: "include",
  });
  const text = await res.text();
  let data = null;
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = text;
    }
  }
  if (!res.ok) {
    if (res.status === 401 && !skipAuth) {
      const authMsg = humanizeJwt401Message(data);
      sessionExpiredToLogin(authMsg);
      const err = new Error(authMsg);
      err.status = 401;
      err.body = data;
      err.sessionEnded = true;
      throw err;
    }
    const msg =
      typeof data === "object" && data !== null
        ? data.detail || data.message || data["hydra:description"] || JSON.stringify(data)
        : data || res.statusText;
    const err = new Error(msg);
    err.status = res.status;
    err.body = data;
    throw err;
  }
  return data;
}

function notifyApiError(e) {
  if (e && e.sessionEnded) {
    return;
  }
  alert(e?.message || String(e));
}

function unwrapCollection(data) {
  if (Array.isArray(data)) {
    return { items: data, total: data.length, next: null };
  }
  if (data && Array.isArray(data["hydra:member"])) {
    const ti = data["hydra:totalItems"];
    const total =
      typeof ti === "number" && !Number.isNaN(ti)
        ? ti
        : ti != null && ti !== ""
          ? Number(ti)
          : data["hydra:member"].length;
    return {
      items: data["hydra:member"],
      total: Number.isFinite(total) ? total : data["hydra:member"].length,
      next: data["hydra:view"]?.["hydra:next"] || null,
    };
  }
  if (data && Array.isArray(data.member))
    return { items: data.member, total: data.totalItems ?? data.member.length, next: data.view?.next || null };
  return { items: [], total: 0, next: null };
}

function buildProductsListUrl() {
  const st = state.productsList;
  const params = new URLSearchParams();
  params.set("page", String(st.page));
  params.set("itemsPerPage", String(st.itemsPerPage));
  const qName = st.name.trim();
  if (qName) {
    params.set("name", qName);
  }
  const qArticle = st.article.trim();
  if (qArticle) {
    params.set("article", qArticle);
  }
  const qSlug = st.slug.trim();
  if (qSlug) {
    params.set("slug", qSlug);
  }
  if (st.categoryId) {
    params.set("categoryId", st.categoryId);
  }
  if (st.stockStatus) {
    params.set("stockStatus", st.stockStatus);
  }
  params.set(`order[${st.orderField}]`, st.orderDir);
  return `/products?${params.toString()}`;
}

function parseSortSelectValue(val) {
  const i = val.lastIndexOf(":");
  if (i <= 0) {
    return { field: "name", dir: "asc" };
  }
  return { field: val.slice(0, i), dir: val.slice(i + 1).toLowerCase() };
}

function syncProductsFilterFormFromState() {
  const name = document.getElementById("pf-name");
  const article = document.getElementById("pf-article");
  const slug = document.getElementById("pf-slug");
  const cat = document.getElementById("pf-category");
  const stock = document.getElementById("pf-stock");
  const sort = document.getElementById("pf-sort");
  const per = document.getElementById("pf-per-page");
  const st = state.productsList;
  if (name instanceof HTMLInputElement) {
    name.value = st.name;
  }
  if (article instanceof HTMLInputElement) {
    article.value = st.article;
  }
  if (slug instanceof HTMLInputElement) {
    slug.value = st.slug;
  }
  if (cat instanceof HTMLSelectElement) {
    cat.value = st.categoryId;
  }
  if (stock instanceof HTMLSelectElement) {
    stock.value = st.stockStatus;
  }
  if (sort instanceof HTMLSelectElement) {
    const v = `${st.orderField}:${st.orderDir}`;
    const has = Array.from(sort.options).some((o) => o.value === v);
    sort.value = has ? v : "name:asc";
    if (!has) {
      const p = parseSortSelectValue(sort.value);
      st.orderField = p.field;
      st.orderDir = p.dir;
    }
  }
  if (per instanceof HTMLSelectElement) {
    per.value = String(st.itemsPerPage);
  }
}

function readProductsFiltersFromForm() {
  const st = state.productsList;
  const name = document.getElementById("pf-name");
  const article = document.getElementById("pf-article");
  const slug = document.getElementById("pf-slug");
  const cat = document.getElementById("pf-category");
  const stock = document.getElementById("pf-stock");
  const sort = document.getElementById("pf-sort");
  const per = document.getElementById("pf-per-page");
  if (name instanceof HTMLInputElement) {
    st.name = name.value;
  }
  if (article instanceof HTMLInputElement) {
    st.article = article.value;
  }
  if (slug instanceof HTMLInputElement) {
    st.slug = slug.value;
  }
  if (cat instanceof HTMLSelectElement) {
    st.categoryId = cat.value;
  }
  if (stock instanceof HTMLSelectElement) {
    st.stockStatus = stock.value;
  }
  if (sort instanceof HTMLSelectElement) {
    const p = parseSortSelectValue(sort.value);
    st.orderField = p.field;
    st.orderDir = p.dir;
  }
  if (per instanceof HTMLSelectElement) {
    st.itemsPerPage = Math.min(100, Math.max(1, parseInt(per.value, 10) || 25));
  }
}

/** id → «Название (slug)» для колонки «Категория» в списке товаров */
async function fetchCategoryIdLabelMap() {
  const data = await apiFetch("/categories?itemsPerPage=500", { headers: API_ACCEPT_JSONLD });
  const { items } = unwrapCollection(data);
  const map = {};
  for (const c of items) {
    map[c.id] = `${c.name || "—"} (${c.slug || "—"})`;
  }
  return map;
}

async function populateProductsFilterCategoryOptions() {
  const sel = document.getElementById("pf-category");
  if (!(sel instanceof HTMLSelectElement)) {
    return;
  }
  const keep = state.productsList.categoryId;
  try {
    const data = await apiFetch("/categories?itemsPerPage=500", { headers: API_ACCEPT_JSONLD });
    const { items } = unwrapCollection(data);
    const rows = flattenCategoriesHierarchicalLabels(items);
    sel.innerHTML = "";
    const all = document.createElement("option");
    all.value = "";
    all.textContent = "Все категории";
    sel.appendChild(all);
    for (const row of rows) {
      const o = document.createElement("option");
      o.value = row.id;
      o.textContent = row.label;
      sel.appendChild(o);
    }
    sel.value = keep;
    if (keep && sel.value !== keep) {
      const miss = document.createElement("option");
      miss.value = keep;
      miss.textContent = `${keep.slice(0, 8)}…`;
      miss.selected = true;
      sel.appendChild(miss);
    }
  } catch {
    /* оставляем «Все» */
  }
}

function configureCrudProductsPanel(sectionId) {
  const panel = document.getElementById("crud-products-filters");
  if (!panel) {
    return;
  }
  const show = sectionId === "products";
  panel.classList.toggle("hidden", !show);
  if (show) {
    syncProductsFilterFormFromState();
    void populateProductsFilterCategoryOptions();
  }
}

function resetProductsListFilters() {
  const st = state.productsList;
  st.page = 1;
  st.itemsPerPage = 25;
  st.name = "";
  st.article = "";
  st.slug = "";
  st.categoryId = "";
  st.stockStatus = "";
  st.orderField = "name";
  st.orderDir = "asc";
  syncProductsFilterFormFromState();
}

function renderProductsPager(pagerEl, totalItems) {
  const st = state.productsList;
  const perPage = st.itemsPerPage;
  const total = typeof totalItems === "number" ? totalItems : 0;
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  const page = st.page;
  pagerEl.classList.remove("hidden");
  pagerEl.innerHTML = "";
  const info = document.createElement("span");
  info.className = "pager-info muted";
  info.textContent = `Всего: ${total} · стр. ${page} из ${totalPages} · по ${perPage}`;
  const prev = document.createElement("button");
  prev.type = "button";
  prev.className = "btn";
  prev.textContent = "Назад";
  prev.disabled = page <= 1;
  prev.addEventListener("click", () => {
    st.page = page - 1;
    void loadCrudTable();
  });
  const next = document.createElement("button");
  next.type = "button";
  next.className = "btn";
  next.textContent = "Вперёд";
  next.disabled = page >= totalPages || total === 0;
  next.addEventListener("click", () => {
    st.page = page + 1;
    void loadCrudTable();
  });
  pagerEl.appendChild(info);
  pagerEl.appendChild(prev);
  pagerEl.appendChild(next);
}

function renderLegacyCrudPager(pagerEl, next, loadedFromPagedUrl) {
  pagerEl.classList.remove("hidden");
  pagerEl.innerHTML = "";
  if (next) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn";
    btn.textContent = "Следующая страница";
    btn.addEventListener("click", () => {
      let path = next;
      try {
        const u = new URL(next, window.location.origin);
        path = u.pathname + u.search;
      } catch {
        const i = next.indexOf("/api/");
        if (i !== -1) path = next.slice(i + 4);
      }
      path = path.replace(/^\/api/, "");
      void loadCrudTable(path);
    });
    pagerEl.appendChild(btn);
  } else if (loadedFromPagedUrl) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn";
    btn.textContent = "К первой странице";
    btn.addEventListener("click", () => void loadCrudTable());
    pagerEl.appendChild(btn);
  } else {
    pagerEl.classList.add("hidden");
  }
}

/** Кириллица и латиница → латинский slug (a-z, 0-9, дефисы) */
const CYRILLIC_TO_LATIN = {
  а: "a",
  б: "b",
  в: "v",
  г: "g",
  д: "d",
  е: "e",
  ё: "yo",
  ж: "zh",
  з: "z",
  и: "i",
  й: "y",
  к: "k",
  л: "l",
  м: "m",
  н: "n",
  о: "o",
  п: "p",
  р: "r",
  с: "s",
  т: "t",
  у: "u",
  ф: "f",
  х: "h",
  ц: "ts",
  ч: "ch",
  ш: "sh",
  щ: "shch",
  ъ: "",
  ы: "y",
  ь: "",
  э: "e",
  ю: "yu",
  я: "ya",
  і: "i",
  ї: "yi",
  є: "ye",
  ґ: "g",
};

function transliterateSlugSource(str) {
  let out = "";
  const s = String(str).toLowerCase();
  for (let i = 0; i < s.length; i++) {
    const ch = s[i];
    if (Object.prototype.hasOwnProperty.call(CYRILLIC_TO_LATIN, ch)) {
      out += CYRILLIC_TO_LATIN[ch];
      continue;
    }
    if (/[a-z0-9]/.test(ch)) {
      out += ch;
      continue;
    }
    if (/\s/.test(ch) || ch === "-" || ch === "_" || ch === "." || ch === "/") {
      out += "-";
    }
  }
  return out
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
}

/**
 * Категории / товары: при создании slug из названия (транслит).
 * Alt фото повторяет slug, пока пользователь не задал свой alt (не пустой и отличный от slug).
 */
function setupCategoryProductSlugAndPhotoAlt(form, isCreate, entity) {
  const nameEl = form.elements.name;
  const slugEl = form.elements.slug;
  const altEl = form.elements.photo_alt;
  if (!(slugEl instanceof HTMLInputElement) || !(altEl instanceof HTMLInputElement)) {
    return;
  }

  const a0 = String(entity?.photo_alt ?? "").trim();
  const s0 = String(entity?.slug ?? "").trim();
  let altManual = a0 !== "" && a0 !== s0;

  const syncAltFromSlug = () => {
    if (!altManual) {
      altEl.value = slugEl.value;
    }
  };

  altEl.addEventListener("input", () => {
    altManual = true;
  });
  altEl.addEventListener("blur", () => {
    if (altEl.value.trim() === "") {
      altManual = false;
      syncAltFromSlug();
    }
  });

  if (isCreate && nameEl instanceof HTMLInputElement) {
    let slugEditedByUser = false;
    slugEl.addEventListener("input", () => {
      slugEditedByUser = true;
      syncAltFromSlug();
    });
    slugEl.addEventListener("blur", () => {
      if (slugEl.value.trim() === "") {
        slugEditedByUser = false;
        slugEl.value = transliterateSlugSource(nameEl.value);
        syncAltFromSlug();
      }
    });
    nameEl.addEventListener("input", () => {
      if (slugEditedByUser) {
        return;
      }
      slugEl.value = transliterateSlugSource(nameEl.value);
      syncAltFromSlug();
    });
    slugEl.value = transliterateSlugSource(nameEl.value);
  } else {
    slugEl.addEventListener("input", syncAltFromSlug);
  }

  syncAltFromSlug();
}

function setToken(t) {
  state.token = t || "";
  if (state.token) localStorage.setItem(STORAGE_KEY, state.token);
  else localStorage.removeItem(STORAGE_KEY);
  updateAuthUi();
}

function updateAuthUi() {
  const logout = document.getElementById("btn-logout");
  const label = document.getElementById("user-label");
  const app = document.getElementById("app");
  const loggedIn = Boolean(state.token);
  logout.classList.toggle("hidden", !loggedIn);
  label.textContent = loggedIn ? "Авторизован" : "";
  app.classList.toggle("app--guest", !loggedIn);
}

/* ——— Navigation ——— */

const SECTIONS = [
  { id: "dashboard", title: "Дашборд", type: "dashboard", admin: true, group: "Обзор" },
  { id: "site_contacts", title: "Контакты сайта", type: "site_contacts", admin: true, group: "Витрина" },
  {
    id: "certificates_catalog",
    title: "Сертификаты и документы",
    type: "certificates_catalog",
    admin: true,
    group: "Витрина",
  },
  { id: "price_list", title: "Прайс-лист", type: "price_list", admin: true, group: "Витрина" },
  { id: "visit_stats", title: "Статистика посещений", type: "visit_stats", admin: true, group: "Витрина" },
  { id: "categories", title: "Категории", type: "crud", resource: "/categories", admin: true, group: "Каталог" },
  { id: "products", title: "Товары", type: "crud", resource: "/products", admin: true, group: "Каталог" },
  { id: "photos", title: "Фотографии", type: "photos", admin: true, group: "Каталог" },
  { id: "favorites", title: "Избранные категории", type: "favorites", admin: true, group: "Каталог" },
  { id: "category_filters", title: "Фильтры категорий", type: "category_filters", admin: true, group: "Каталог" },
  { id: "orders", title: "Заказы", type: "crud", resource: "/orders", admin: true, hideNew: true, group: "Заказы" },
  { id: "api-playground", title: "Конструктор запросов", type: "api", admin: false, group: "Инструменты" },
];

function buildNav() {
  const nav = document.getElementById("nav");
  nav.innerHTML = "";
  let lastGroup = null;
  SECTIONS.forEach((s) => {
    if (s.group && s.group !== lastGroup) {
      lastGroup = s.group;
      const g = document.createElement("div");
      g.className = "nav-group";
      g.textContent = s.group;
      nav.appendChild(g);
    }
    const a = document.createElement("a");
    a.href = "#";
    a.dataset.section = s.id;
    a.textContent = s.title;
    nav.appendChild(a);
  });

  nav.querySelectorAll("a[data-section]").forEach((el) => {
    el.addEventListener("click", (e) => {
      e.preventDefault();
      goSection(el.dataset.section);
    });
  });
}

function goSection(id) {
  ensureCertificatesCatalogView();
  if (id !== "login" && id !== "api-playground" && !state.token) {
    id = "login";
  }
  state.section = id;
  document.querySelectorAll(".sidebar nav a").forEach((a) => {
    a.classList.toggle("active", a.dataset.section === id && id !== "login");
  });
  const title = document.getElementById("page-title");
  const sec = SECTIONS.find((s) => s.id === id);
  title.textContent = sec ? sec.title : id;

  document.getElementById("view-login").classList.add("hidden");
  document.getElementById("view-crud").classList.add("hidden");
  document.getElementById("view-order-detail").classList.add("hidden");
  document.getElementById("view-api").classList.add("hidden");
  document.getElementById("view-favorites").classList.add("hidden");
  document.getElementById("view-category-filters").classList.add("hidden");
  document.getElementById("view-photos").classList.add("hidden");
  document.getElementById("view-site-contacts").classList.add("hidden");
  document.getElementById("view-price-list").classList.add("hidden");
  document.getElementById("view-visit-stats").classList.add("hidden");
  document.getElementById("view-certificates-catalog").classList.add("hidden");
  document.getElementById("view-dashboard").classList.add("hidden");

  if (id === "login") {
    document.getElementById("view-login").classList.remove("hidden");
    title.textContent = "Вход";
    return;
  }
  if (id === "api-playground") {
    document.getElementById("view-api").classList.remove("hidden");
    return;
  }
  if (id === "dashboard") {
    state.orderDetailId = null;
    document.getElementById("view-dashboard").classList.remove("hidden");
    void loadDashboard();
    return;
  }
  if (id === "favorites") {
    document.getElementById("view-favorites").classList.remove("hidden");
    loadFavoritesTable();
    return;
  }
  if (id === "category_filters") {
    document.getElementById("view-category-filters").classList.remove("hidden");
    initCategoryFiltersSection();
    return;
  }
  if (id === "photos") {
    document.getElementById("view-photos").classList.remove("hidden");
    void initPhotosSection();
    return;
  }
  if (id === "site_contacts") {
    state.orderDetailId = null;
    document.getElementById("view-site-contacts").classList.remove("hidden");
    void loadSiteContactsForm();
    return;
  }
  if (id === "price_list") {
    state.orderDetailId = null;
    document.getElementById("view-price-list").classList.remove("hidden");
    void loadPriceListSection();
    return;
  }
  if (id === "visit_stats") {
    state.orderDetailId = null;
    document.getElementById("view-visit-stats").classList.remove("hidden");
    void loadVisitStats();
    return;
  }
  if (id === "certificates_catalog") {
    state.orderDetailId = null;
    document.getElementById("view-certificates-catalog").classList.remove("hidden");
    void loadCertificatesCatalogSection();
    return;
  }
  state.orderDetailId = null;
  document.getElementById("view-order-detail").classList.add("hidden");
  document.getElementById("view-crud").classList.remove("hidden");
  configureCrudProductsPanel(id);
  void loadCrudTable();
}

/* ——— CRUD schemas ——— */

const CRUD_SCHEMA = {
  products: {
    formClass: "product-form",
    fieldGroups: [
      {
        id: "main",
        title: "Основное",
        hint: "Минимум для публикации: категория, название, slug и статус наличия.",
      },
      { id: "commerce", title: "Продажи и склад", hint: "Данные для карточки товара и прайс-листа." },
      { id: "media", title: "Обложка", hint: "Основные фотографии удобнее загружать в разделе «Фотографии» после создания товара." },
      { id: "content", title: "Описание и характеристики", hint: "Характеристики используются на странице товара и в фильтрах категорий." },
      { id: "files", title: "Чертежи и документы", hint: "Можно заполнить позже. Формат — JSON-массивы.", collapsible: true },
      { id: "seo", title: "SEO", hint: "Если оставить пустым, витрина сможет использовать название и описание товара.", collapsible: true },
    ],
    listColumns: [
      { key: "name", label: "Название" },
      { key: "category_id", label: "Категория" },
      { key: "slug", label: "Slug" },
      { key: "article", label: "Артикул" },
      { key: "price", label: "Цена" },
      { key: "stock_status", label: "Склад" },
    ],
    fields: [
      { key: "category_id", label: "Категория", type: "product_category", required: true, group: "main", full: true },
      {
        key: "name",
        label: "Название",
        type: "text",
        required: true,
        group: "main",
        placeholder: "Например: Манометр ТМ-510",
        full: true,
      },
      {
        key: "slug",
        label: "Slug",
        type: "text",
        required: true,
        group: "main",
        placeholder: "manometr-tm-510",
        hint: "Заполняется автоматически из названия, но можно поправить вручную.",
      },
      { key: "article", label: "Артикул", type: "text", group: "main", placeholder: "ТМ-510" },
      { key: "price", label: "Цена", type: "text", group: "commerce", placeholder: "12500.00", inputMode: "decimal" },
      {
        key: "stock_status",
        label: "Статус наличия",
        type: "select",
        group: "commerce",
        options: [
          { v: "on_order", t: "Под заказ" },
          { v: "in_stock", t: "В наличии" },
        ],
      },
      {
        key: "manufacturing_time",
        label: "Срок изготовления",
        type: "text",
        group: "commerce",
        placeholder: "Например: 10 рабочих дней",
      },
      { key: "gost_number", label: "ГОСТ", type: "text", group: "commerce", placeholder: "ГОСТ 2405-88" },
      { key: "has_verification", label: "Поверка", type: "checkbox", group: "commerce", full: true },
      { key: "photo", label: "Фото URL", type: "text", group: "media", placeholder: "/uploads/products/...", full: true },
      {
        key: "photo_alt",
        label: "Alt фото",
        type: "text",
        group: "media",
        hint: "По умолчанию повторяет slug, пока поле не изменили вручную.",
        full: true,
      },
      { key: "description", label: "Описание", type: "textarea", group: "content", rows: 5, full: true },
      {
        key: "technical_specs",
        label: "Тех. характеристики (JSON)",
        type: "json",
        group: "content",
        rows: 7,
        hint: 'Например: { "Диаметр": "100 мм", "Класс точности": "1.5" }',
        full: true,
      },
      { key: "drawings", label: "Чертежи (JSON)", type: "json", group: "files", rows: 4, full: true },
      { key: "documents", label: "Документы (JSON)", type: "json", group: "files", rows: 4, full: true },
      { key: "certificates", label: "Сертификаты (JSON)", type: "json", group: "files", rows: 4, full: true },
      { key: "meta_title", label: "Meta title", type: "text", group: "seo", full: true },
      { key: "meta_description", label: "Meta description", type: "textarea", group: "seo", rows: 3, full: true },
    ],
  },
  categories: {
    listColumns: [
      { key: "name", label: "Название" },
      { key: "slug", label: "Slug" },
      { key: "is_favorite_main", label: "Главная" },
      { key: "is_favorite_sidebar", label: "Сайдбар" },
      { key: "sort_order", label: "Порядок" },
      { key: "display_mode", label: "Режим" },
    ],
    fields: [
      { key: "parent_id", label: "Родительская категория", type: "category_parent" },
      { key: "name", label: "Название", type: "text", required: true },
      { key: "slug", label: "Slug", type: "text", required: true },
      { key: "description", label: "Описание", type: "textarea" },
      { key: "photo", label: "Фото URL", type: "text" },
      { key: "photo_alt", label: "Alt фото", type: "text" },
      { key: "is_favorite_main", label: "Избранное на главной", type: "checkbox" },
      { key: "is_favorite_sidebar", label: "Избранное в сайдбаре", type: "checkbox" },
      { key: "sort_order", label: "Порядок сортировки", type: "number" },
      {
        key: "display_mode",
        label: "Режим отображения",
        type: "select",
        options: [
          { v: "subcategories_only", t: "Только подкатегории" },
          { v: "products_only", t: "Только товары" },
        ],
      },
      { key: "aggregate_products", label: "Агрегировать товары", type: "checkbox" },
      {
        key: "also_bought_product_ids",
        label: "С этим товаром покупают",
        type: "category_recommendations",
      },
      {
        key: "filter_config",
        label: 'Фильтры витрины (JSON: keys[], labels{}) — удобнее в разделе «Фильтры категорий»',
        type: "json",
        editOnly: true,
      },
      { key: "meta_title", label: "Meta title", type: "text" },
      { key: "meta_description", label: "Meta description", type: "textarea" },
    ],
  },
  orders: {
    listColumns: [
      { key: "order_number", label: "№" },
      { key: "customer_name", label: "Клиент" },
      { key: "customer_email", label: "Email" },
      { key: "customer_inn", label: "ИНН" },
      { key: "status", label: "Статус" },
      { key: "created_at", label: "Создан" },
    ],
    fields: [
      { key: "order_number", label: "Номер заказа", type: "text", required: true, editOnly: true },
      { key: "customer_name", label: "Имя", type: "text", required: true },
      { key: "customer_company", label: "Компания", type: "text" },
      { key: "customer_inn", label: "ИНН", type: "text" },
      { key: "customer_phone", label: "Телефон", type: "text", required: true },
      { key: "customer_email", label: "Email", type: "text", required: true },
      { key: "items", label: "Позиции", type: "order_items", required: true },
      { key: "attachments", label: "Вложения (JSON)", type: "json" },
      { key: "comment", label: "Комментарий", type: "textarea" },
      { key: "total_amount", label: "Сумма", type: "text" },
      {
        key: "status",
        label: "Статус",
        type: "select",
        options: [
          { v: "new", t: "Новый" },
          { v: "processing", t: "В работе" },
          { v: "completed", t: "Завершён" },
          { v: "cancelled", t: "Отменён" },
        ],
        required: true,
      },
    ],
  },
};

function buildCategoryChildrenMap(items) {
  const children = new Map();
  for (const c of items) {
    const p = c.parent_id != null && c.parent_id !== "" ? c.parent_id : "__root__";
    if (!children.has(p)) {
      children.set(p, []);
    }
    children.get(p).push(c);
  }
  for (const [, arr] of children) {
    arr.sort(
      (a, b) =>
        (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0) ||
        String(a.name || "").localeCompare(String(b.name || ""), "ru"),
    );
  }
  return children;
}

function buildCategoryTreeHead() {
  const head = document.getElementById("category-tree-head");
  if (!head) {
    return;
  }
  const schema = CRUD_SCHEMA.categories;
  head.innerHTML = `<div class="category-tree-grid category-tree-head-row">
    <span class="category-tree-hcell" aria-hidden="true"></span>
    ${schema.listColumns.map((c) => `<span class="category-tree-hcell">${escapeHtml(c.label)}</span>`).join("")}
    <span class="category-tree-hcell category-tree-hcell--actions">Действия</span>
  </div>`;
}

function categoryWritablePayloadFromEntity(entity) {
  const payload = {};
  for (const f of CRUD_SCHEMA.categories.fields) {
    const k = f.key;
    let v = entity[k];
    if (v === undefined) {
      if (k === "parent_id") {
        payload[k] = null;
      } else if (f.type === "checkbox") {
        payload[k] = false;
      }
      continue;
    }
    if (k === "parent_id" && (v === "" || v === null)) {
      payload[k] = null;
    } else {
      payload[k] = v;
    }
  }
  return payload;
}

function productWritablePayloadFromEntity(entity) {
  const payload = {};
  for (const f of CRUD_SCHEMA.products.fields) {
    const k = f.key;
    let v = entity[k];
    if (v === undefined) {
      if (f.type === "checkbox") {
        payload[k] = false;
      }
      continue;
    }
    if (k === "category_id" && (v === "" || v == null)) {
      payload[k] = null;
      continue;
    }
    payload[k] = v;
  }
  return payload;
}

async function persistCategorySiblingOrderFromUl(ul) {
  const lis = [...ul.children].filter((n) => n.matches("li.category-tree-node"));
  for (let i = 0; i < lis.length; i++) {
    const id = lis[i].dataset.categoryId;
    if (!id) {
      continue;
    }
    const entity = await apiFetch(`/categories/${id}`);
    const payload = categoryWritablePayloadFromEntity(entity);
    payload.sort_order = i;
    await apiFetch(`/categories/${id}`, { method: "PUT", body: JSON.stringify(payload) });
  }
}

function renderCategoryTreeUl(parentKey, childrenMap) {
  const ul = document.createElement("ul");
  ul.className = "category-tree-list";
  ul.dataset.parentKey = parentKey;
  const kids = childrenMap.get(parentKey) || [];
  const schema = CRUD_SCHEMA.categories;
  for (const row of kids) {
    const li = document.createElement("li");
    li.className = "category-tree-node";
    if (row.is_favorite_main || row.is_favorite_sidebar) {
      li.classList.add("category-tree-node--favorite");
    }
    li.dataset.categoryId = row.id;
    const rowEl = document.createElement("div");
    rowEl.className = "category-tree-grid category-tree-row";
    const phBtn = `<button type="button" class="btn btn-ghost btn-ph" data-id="${escapeHtml(row.id)}" data-ph-label="${escapeHtml(row.name || "")}">Фотографии</button>`;
    rowEl.innerHTML = `
      <span class="category-drag-handle" draggable="true" title="Тяните, чтобы поменять порядок среди соседей">⋮⋮</span>
      ${schema.listColumns.map((c) => `<span class="category-tree-cell">${escapeHtml(formatCell(row[c.key]))}</span>`).join("")}
      <div class="actions">
        <button type="button" class="btn btn-edit" data-id="${escapeHtml(row.id)}">Изменить</button>
        ${phBtn}
        <button type="button" class="btn btn-danger btn-del" data-id="${escapeHtml(row.id)}">Удалить</button>
      </div>`;
    li.appendChild(rowEl);
    const nested = renderCategoryTreeUl(row.id, childrenMap);
    if (nested.children.length > 0) {
      li.appendChild(nested);
    }
    ul.appendChild(li);
  }
  return ul;
}

function renderCategoriesAdminTree(items, rootEl) {
  if (!rootEl) {
    return;
  }
  const childrenMap = buildCategoryChildrenMap(items);
  const ul = renderCategoryTreeUl("__root__", childrenMap);
  rootEl.innerHTML = "";
  rootEl.appendChild(ul);
}

function bindCategoryTreeRowActions(sec, rootEl) {
  if (!rootEl) {
    return;
  }
  rootEl.querySelectorAll(".btn-edit").forEach((b) => {
    b.addEventListener("click", () => openModal(sec.id, b.dataset.id));
  });
  rootEl.querySelectorAll(".btn-ph").forEach((b) => {
    b.addEventListener("click", () => {
      openPhotosForEntity("category", b.dataset.id, b.dataset.phLabel || "");
    });
  });
  rootEl.querySelectorAll(".btn-del").forEach((b) => {
    b.addEventListener("click", () => deleteRow(sec.resource, b.dataset.id));
  });
}

function initCategoryTreeDnD() {
  const rootEl = document.getElementById("category-tree-root");
  if (!rootEl || rootEl.dataset.dndWired === "1") {
    return;
  }
  rootEl.dataset.dndWired = "1";
  let draggedLi = null;

  rootEl.addEventListener("dragstart", (e) => {
    const h = e.target.closest(".category-drag-handle");
    if (!h || !rootEl.contains(h)) {
      return;
    }
    draggedLi = h.closest("li.category-tree-node");
    if (!draggedLi) {
      return;
    }
    e.dataTransfer.effectAllowed = "move";
    e.dataTransfer.setData("text/plain", draggedLi.dataset.categoryId);
    draggedLi.classList.add("category-row-dragging");
  });

  rootEl.addEventListener("dragend", () => {
    if (draggedLi) {
      draggedLi.classList.remove("category-row-dragging");
    }
    draggedLi = null;
  });

  rootEl.addEventListener("dragover", (e) => {
    if (!draggedLi) {
      return;
    }
    const targetLi = e.target.closest("li.category-tree-node");
    if (!targetLi || targetLi === draggedLi) {
      return;
    }
    if (targetLi.parentElement !== draggedLi.parentElement) {
      return;
    }
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";
  });

  rootEl.addEventListener("drop", async (e) => {
    if (!draggedLi) {
      return;
    }
    const targetLi = e.target.closest("li.category-tree-node");
    if (!targetLi || targetLi === draggedLi) {
      return;
    }
    if (targetLi.parentElement !== draggedLi.parentElement) {
      return;
    }
    e.preventDefault();
    const ul = draggedLi.parentElement;
    const rect = targetLi.getBoundingClientRect();
    const before = e.clientY < rect.top + rect.height / 2;
    if (before) {
      ul.insertBefore(draggedLi, targetLi);
    } else {
      ul.insertBefore(draggedLi, targetLi.nextSibling);
    }
    try {
      rootEl.classList.add("category-tree--saving");
      await persistCategorySiblingOrderFromUl(ul);
    } catch (err) {
      notifyApiError(err);
      await loadCrudTable();
    } finally {
      rootEl.classList.remove("category-tree--saving");
    }
  });
}

async function loadCrudTable(url = null) {
  const sec = SECTIONS.find((s) => s.id === state.section);
  if (!sec || sec.type !== "crud") return;
  const schema = CRUD_SCHEMA[sec.id];
  const tbody = document.getElementById("crud-tbody");
  const thead = document.getElementById("crud-thead");
  const empty = document.getElementById("crud-empty");
  const pager = document.getElementById("crud-pager");
  const newBtn = document.getElementById("btn-new");
  newBtn.classList.toggle("hidden", Boolean(sec.hideNew));
  newBtn.textContent = sec.id === "products" ? "Добавить товар" : "Создать";

  let fetchUrl;
  if (sec.id === "categories") {
    fetchUrl = sec.resource.includes("?") ? `${sec.resource}&itemsPerPage=500` : `${sec.resource}?itemsPerPage=500`;
  } else if (url) {
    fetchUrl = url;
  } else if (sec.id === "products") {
    fetchUrl = buildProductsListUrl();
  } else {
    fetchUrl = sec.resource;
  }
  state.collectionUrl = sec.resource;

  try {
    const data = await apiFetch(fetchUrl, { headers: API_ACCEPT_JSONLD });
    empty.textContent = "Нет записей";
    let { items, total, next } = unwrapCollection(data);
    state.nextPageUrl = next;

    if (sec.id === "products") {
      const st = state.productsList;
      const perPage = st.itemsPerPage;
      const totalN = typeof total === "number" ? total : 0;
      const totalPages = Math.max(1, Math.ceil(totalN / perPage));
      if (st.page > totalPages) {
        st.page = totalPages;
        await loadCrudTable();
        return;
      }
    }

    const crudTable = document.getElementById("crud-table");
    const categoryTreeWrap = document.getElementById("category-tree-wrap");
    const categoryTreeHead = document.getElementById("category-tree-head");
    const categoryTreeRoot = document.getElementById("category-tree-root");

    if (sec.id === "categories" && crudTable && categoryTreeWrap) {
      crudTable.classList.add("hidden");
      categoryTreeWrap.classList.remove("hidden");
    } else if (crudTable && categoryTreeWrap) {
      crudTable.classList.remove("hidden");
      categoryTreeWrap.classList.add("hidden");
    }

    let categoryLabels = {};
    if (sec.id === "products") {
      try {
        categoryLabels = await fetchCategoryIdLabelMap();
      } catch {
        categoryLabels = {};
      }
    }

    if (sec.id === "categories") {
      thead.innerHTML = "";
      tbody.innerHTML = "";
      buildCategoryTreeHead();
      if (items.length === 0) {
        if (categoryTreeHead) {
          categoryTreeHead.classList.add("hidden");
        }
        if (categoryTreeRoot) {
          categoryTreeRoot.innerHTML = "";
        }
        empty.classList.remove("hidden");
      } else {
        empty.classList.add("hidden");
        if (categoryTreeHead) {
          categoryTreeHead.classList.remove("hidden");
        }
        renderCategoriesAdminTree(items, categoryTreeRoot);
        bindCategoryTreeRowActions(sec, categoryTreeRoot);
      }
      pager.classList.add("hidden");
    } else {
      thead.innerHTML = `<tr>${schema.listColumns.map((c) => `<th>${escapeHtml(c.label)}</th>`).join("")}<th class="actions">Действия</th></tr>`;
      tbody.innerHTML = "";
      if (items.length === 0) {
        empty.classList.remove("hidden");
      } else {
        empty.classList.add("hidden");
        for (const row of items) {
          const tr = document.createElement("tr");
          const phBtn =
            sec.id === "products"
              ? `<button type="button" class="btn btn-ghost btn-ph" data-id="${escapeHtml(row.id)}" data-ph-label="${escapeHtml(row.name || "")}">Фотографии</button>`
              : "";
          tr.innerHTML =
            schema.listColumns
              .map((c) => {
                if (sec.id === "products" && c.key === "category_id") {
                  const cid = row.category_id;
                  const text =
                    cid && Object.prototype.hasOwnProperty.call(categoryLabels, cid)
                      ? categoryLabels[cid]
                      : cid
                        ? String(cid)
                        : "—";
                  return `<td>${escapeHtml(text)}</td>`;
                }
                return `<td>${escapeHtml(formatCell(row[c.key]))}</td>`;
              })
              .join("") +
            `<td class="actions">
            <button type="button" class="btn btn-edit" data-id="${escapeHtml(row.id)}">Изменить</button>
            ${phBtn}
            <button type="button" class="btn btn-danger btn-del" data-id="${escapeHtml(row.id)}">Удалить</button>
          </td>`;
          tbody.appendChild(tr);
        }
        tbody.querySelectorAll(".btn-edit").forEach((b) => {
          b.addEventListener("click", () => openModal(sec.id, b.dataset.id));
        });
        tbody.querySelectorAll(".btn-ph").forEach((b) => {
          b.addEventListener("click", () => {
            openPhotosForEntity("product", b.dataset.id, b.dataset.phLabel || "");
          });
        });
        tbody.querySelectorAll(".btn-del").forEach((b) => {
          b.addEventListener("click", () => deleteRow(sec.resource, b.dataset.id));
        });
        if (sec.id === "orders") {
          tbody.querySelectorAll("tr").forEach((tr, i) => {
            const id = items[i].id;
            const num = items[i].order_number;
            tr.style.cursor = "pointer";
            tr.addEventListener("click", (ev) => {
              if (ev.target.closest(".btn")) return;
              showOrderDetail(id, num);
            });
          });
        }
      }

      if (sec.id === "products") {
        renderProductsPager(pager, typeof total === "number" ? total : 0);
      } else {
        renderLegacyCrudPager(pager, next, Boolean(url));
      }
    }
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    tbody.innerHTML = "";
    const crudTable = document.getElementById("crud-table");
    const treeWrap = document.getElementById("category-tree-wrap");
    const treeRoot = document.getElementById("category-tree-root");
    if (crudTable && treeWrap) {
      treeWrap.classList.add("hidden");
      crudTable.classList.remove("hidden");
    }
    if (treeRoot) {
      treeRoot.innerHTML = "";
    }
    empty.textContent = e.message || String(e);
    empty.classList.remove("hidden");
    pager.classList.add("hidden");
  }
}

function formatCell(v) {
  if (v === null || v === undefined) return "—";
  if (typeof v === "boolean") return v ? "да" : "нет";
  if (typeof v === "object") return JSON.stringify(v);
  return String(v);
}

function escapeHtml(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

async function deleteRow(resource, id) {
  if (!confirm("Удалить запись?")) return;
  try {
    await apiFetch(`${resource}/${id}`, { method: "DELETE" });
    loadCrudTable();
  } catch (e) {
    notifyApiError(e);
  }
}

function getSelfAndDescendantIds(items, categoryId) {
  const byParent = new Map();
  for (const c of items) {
    const p = c.parent_id != null && c.parent_id !== "" ? c.parent_id : null;
    if (!byParent.has(p)) {
      byParent.set(p, []);
    }
    byParent.get(p).push(c.id);
  }
  const out = new Set([categoryId]);
  const stack = [...(byParent.get(categoryId) || [])];
  while (stack.length) {
    const id = stack.pop();
    if (out.has(id)) {
      continue;
    }
    out.add(id);
    for (const ch of byParent.get(id) || []) {
      stack.push(ch);
    }
  }
  return out;
}

/** Для товаров: в списке только категории, где можно размещать товары (не «только подкатегории»). */
function flattenCategoriesForProductAssignment(items) {
  const children = new Map();
  for (const c of items) {
    const p = c.parent_id != null && c.parent_id !== "" ? c.parent_id : "__root__";
    if (!children.has(p)) {
      children.set(p, []);
    }
    children.get(p).push(c);
  }
  for (const [, arr] of children) {
    arr.sort(
      (a, b) =>
        (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0) ||
        String(a.name || "").localeCompare(String(b.name || ""), "ru"),
    );
  }
  const out = [];
  function walk(parentKey, depth) {
    for (const c of children.get(parentKey) || []) {
      const mode = c.display_mode != null && c.display_mode !== "" ? c.display_mode : "subcategories_only";
      if (mode !== "subcategories_only") {
        const pad = depth ? `${"· ".repeat(depth)}` : "";
        out.push({ id: c.id, label: `${pad}${c.name} (${c.slug})` });
      }
      walk(c.id, depth + 1);
    }
  }
  walk("__root__", 0);
  return out;
}

/** Порядок как в дереве: sort_order, затем имя; подписи с отступом по уровню */
function flattenCategoriesHierarchicalLabels(items) {
  const children = new Map();
  for (const c of items) {
    const p = c.parent_id != null && c.parent_id !== "" ? c.parent_id : "__root__";
    if (!children.has(p)) {
      children.set(p, []);
    }
    children.get(p).push(c);
  }
  for (const [, arr] of children) {
    arr.sort(
      (a, b) =>
        (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0) ||
        String(a.name || "").localeCompare(String(b.name || ""), "ru"),
    );
  }
  const out = [];
  function walk(parentKey, depth) {
    for (const c of children.get(parentKey) || []) {
      const pad = depth ? `${"· ".repeat(depth)}` : "";
      out.push({ id: c.id, label: `${pad}${c.name} (${c.slug})` });
      walk(c.id, depth + 1);
    }
  }
  walk("__root__", 0);
  return out;
}

function buildFormFields(schemaKey, entity, isCreate) {
  const schema = CRUD_SCHEMA[schemaKey];
  const frag = document.createDocumentFragment();
  const groupBodies = new Map();
  if (Array.isArray(schema.fieldGroups)) {
    for (const group of schema.fieldGroups) {
      const section = document.createElement(group.collapsible ? "details" : "section");
      section.className = `form-section form-section--${group.id}`;
      if (group.collapsible && group.open) {
        section.open = true;
      }

      if (group.collapsible) {
        const summary = document.createElement("summary");
        summary.className = "form-section-title";
        summary.textContent = group.title;
        section.appendChild(summary);
      } else {
        const title = document.createElement("h3");
        title.className = "form-section-title";
        title.textContent = group.title;
        section.appendChild(title);
      }

      if (group.hint) {
        const hint = document.createElement("p");
        hint.className = "form-section-hint muted small";
        hint.textContent = group.hint;
        section.appendChild(hint);
      }

      const body = document.createElement("div");
      body.className = "form-section-grid";
      section.appendChild(body);
      groupBodies.set(group.id, body);
      frag.appendChild(section);
    }
  }

  for (const f of schema.fields) {
    if (f.editOnly && isCreate) continue;
    const target = f.group && groupBodies.has(f.group) ? groupBodies.get(f.group) : frag;
    const wrap = document.createElement("label");
    wrap.className = "form-field";
    wrap.dataset.field = f.key;
    if (f.full) {
      wrap.classList.add("form-field--full");
    }
    const labelText = document.createElement("span");
    labelText.className = "form-field-label";
    labelText.textContent = f.required ? `${f.label} *` : f.label;
    let input;
    if (f.type === "textarea") {
      input = document.createElement("textarea");
      input.rows = f.rows || 4;
      if (entity && entity[f.key] !== undefined && entity[f.key] !== null) {
        input.value = String(entity[f.key]);
      }
    } else if (f.type === "checkbox") {
      input = document.createElement("input");
      input.type = "checkbox";
      input.name = f.key;
      input.checked = entity ? Boolean(entity[f.key]) : false;
      wrap.classList.add("form-field--checkbox");
      wrap.appendChild(input);
      wrap.appendChild(labelText);
      if (f.hint) {
        const hint = document.createElement("span");
        hint.className = "form-field-hint muted small";
        hint.textContent = f.hint;
        wrap.appendChild(hint);
      }
      target.appendChild(wrap);
      continue;
    } else if (f.type === "select") {
      input = document.createElement("select");
      input.name = f.key;
      for (const o of f.options) {
        const opt = document.createElement("option");
        opt.value = o.v;
        opt.textContent = o.t;
        input.appendChild(opt);
      }
      if (entity && entity[f.key] !== undefined) input.value = entity[f.key];
    } else if (f.type === "category_parent" || f.type === "product_category") {
      input = document.createElement("select");
      input.name = f.key;
      input.disabled = true;
      const loading = document.createElement("option");
      loading.value = "";
      loading.textContent = "Загрузка категорий…";
      input.appendChild(loading);
    } else if (f.type === "category_recommendations") {
      const selectedIds = normalizeProductIdList(entity?.[f.key]);
      input = document.createElement("input");
      input.type = "hidden";
      input.name = f.key;
      input.value = JSON.stringify(selectedIds);
      wrap.classList.add("admin-product-picker-label");
      const picker = document.createElement("div");
      picker.className = "admin-product-picker";
      picker.dataset.productPickerField = f.key;
      picker.innerHTML = `
        <input type="search" class="admin-product-picker__search" placeholder="Поиск по названию, артикулу или slug" disabled />
        <div class="admin-product-picker__selected muted small">Выбранные товары загружаются…</div>
        <div class="admin-product-picker__list" aria-live="polite">Загрузка товаров…</div>
        <p class="muted small admin-product-picker__hint">Выберите товары, которые нужно показывать на страницах товаров этой категории. Порядок сохраняется по очередности выбора.</p>
      `;
      wrap.appendChild(labelText);
      wrap.appendChild(input);
      wrap.appendChild(picker);
      target.appendChild(wrap);
      continue;
    } else if (f.type === "number") {
      input = document.createElement("input");
      input.type = "number";
      input.name = f.key;
      if (entity && entity[f.key] !== undefined) input.value = entity[f.key];
    } else if (f.type === "order_items") {
      const preview = document.createElement("div");
      preview.innerHTML = renderOrderItemsTableHtml(entity?.[f.key]);
      const hint = document.createElement("p");
      hint.className = "muted small order-items-json-hint";
      hint.textContent = "JSON позиций (для точного редактирования):";
      input = document.createElement("textarea");
      input.rows = 6;
      input.name = f.key;
      input.className = "order-items-json";
      input.spellcheck = false;
      const v = entity?.[f.key];
      input.value = v === undefined || v === null ? "" : typeof v === "string" ? v : JSON.stringify(v, null, 2);
      wrap.appendChild(preview);
      wrap.appendChild(hint);
    } else if (f.type === "json") {
      input = document.createElement("textarea");
      input.rows = f.rows || 5;
      input.name = f.key;
      input.spellcheck = false;
      const v = entity?.[f.key];
      input.value = v === undefined || v === null ? "" : typeof v === "string" ? v : JSON.stringify(v, null, 2);
    } else {
      input = document.createElement("input");
      input.type = "text";
      input.name = f.key;
      if (entity && entity[f.key] !== undefined && entity[f.key] !== null) input.value = entity[f.key];
    }
    input.name = f.key;
    if (f.placeholder) {
      input.placeholder = f.placeholder;
    }
    if (f.inputMode) {
      input.inputMode = f.inputMode;
    }
    if (f.required) input.required = true;
    wrap.appendChild(labelText);
    wrap.appendChild(input);
    if (f.hint) {
      const hint = document.createElement("span");
      hint.className = "form-field-hint muted small";
      hint.textContent = f.hint;
      wrap.appendChild(hint);
    }
    target.appendChild(wrap);
  }
  if (schema.formClass) {
    const root = document.createElement("div");
    root.className = schema.formClass;
    root.appendChild(frag);
    return root;
  }
  return frag;
}

function readFormPayload(form, schemaKey, isCreate) {
  const schema = CRUD_SCHEMA[schemaKey];
  const payload = {};
  for (const f of schema.fields) {
    if (f.editOnly && isCreate) continue;
    const el = form.elements[f.key];
    if (!el) continue;
    if (f.type === "checkbox") {
      payload[f.key] = el.checked;
      continue;
    }
    let val = el.value;
    if (f.type === "number") {
      payload[f.key] = val === "" ? 0 : Number(val);
      continue;
    }
    if (f.type === "category_parent") {
      payload[f.key] = val.trim() === "" ? null : val;
      continue;
    }
    if (f.type === "product_category") {
      if (val.trim() === "") {
        throw new Error("Выберите категорию");
      }
      payload[f.key] = val.trim();
      continue;
    }
    if (f.type === "category_recommendations") {
      const ids = normalizeProductIdList(val);
      payload[f.key] = ids.length ? ids : null;
      continue;
    }
    if (f.type === "json" || f.type === "order_items") {
      const t = val.trim();
      if (t === "") {
        payload[f.key] = null;
      } else {
        try {
          payload[f.key] = JSON.parse(t);
        } catch {
          throw new Error(`Некорректный JSON в поле «${f.label}»`);
        }
      }
      continue;
    }
    // DECIMAL в БД не принимает ""; пустое значение должно быть null
    if (f.key === "price" || f.key === "total_amount") {
      const t = String(val).trim();
      payload[f.key] = t === "" ? null : t;
      continue;
    }
    if (f.key === "parent_id" && val.trim() === "") {
      payload[f.key] = null;
      continue;
    }
    payload[f.key] = val;
  }
  if (schemaKey === "categories" && payload.parent_id === "") payload.parent_id = null;
  return payload;
}

async function populateProductCategorySelect(form, entity) {
  const sel = form.elements.category_id;
  if (!(sel instanceof HTMLSelectElement)) {
    return;
  }
  try {
    const data = await apiFetch("/categories?itemsPerPage=500", { headers: API_ACCEPT_JSONLD });
    const { items } = unwrapCollection(data);
    const rows = flattenCategoriesForProductAssignment(items);
    sel.innerHTML = "";
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "— Выберите категорию —";
    sel.appendChild(placeholder);
    for (const row of rows) {
      const o = document.createElement("option");
      o.value = row.id;
      o.textContent = row.label;
      sel.appendChild(o);
    }
    const want = entity?.category_id != null && entity?.category_id !== "" ? String(entity.category_id) : "";
    sel.value = want;
    if (want && sel.value !== want) {
      const miss = document.createElement("option");
      miss.value = want;
      miss.textContent = `${want} (не в списке)`;
      miss.selected = true;
      sel.appendChild(miss);
    }
    sel.disabled = false;
  } catch (e) {
    sel.innerHTML = "";
    const err = document.createElement("option");
    err.value = "";
    err.textContent = `Ошибка загрузки: ${e.message}`;
    sel.appendChild(err);
    sel.disabled = true;
  }
}

function normalizeProductIdList(value) {
  let raw = value;
  if (typeof raw === "string") {
    const t = raw.trim();
    if (!t) {
      return [];
    }
    try {
      raw = JSON.parse(t);
    } catch {
      raw = t.split(/[,\n]/);
    }
  }
  if (!Array.isArray(raw)) {
    return [];
  }
  const seen = new Set();
  const ids = [];
  for (const item of raw) {
    if (typeof item !== "string") {
      continue;
    }
    const id = item.trim();
    if (!id || seen.has(id)) {
      continue;
    }
    seen.add(id);
    ids.push(id);
  }
  return ids;
}

async function populateCategoryRecommendationsPicker(form, entity) {
  const field = "also_bought_product_ids";
  const hidden = form.elements[field];
  const root = form.querySelector(`[data-product-picker-field="${field}"]`);
  if (!(hidden instanceof HTMLInputElement) || !root) {
    return;
  }

  const search = root.querySelector(".admin-product-picker__search");
  const selectedBox = root.querySelector(".admin-product-picker__selected");
  const list = root.querySelector(".admin-product-picker__list");
  if (!(search instanceof HTMLInputElement) || !selectedBox || !list) {
    return;
  }

  let selectedIds = normalizeProductIdList(entity?.[field]);
  hidden.value = JSON.stringify(selectedIds);

  try {
    const products = (await fetchAllHydraMembers("/products?order[name]=asc"))
      .filter((p) => p && typeof p.id === "string")
      .sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), "ru"));
    const byId = new Map(products.map((p) => [p.id, p]));

    function syncHidden() {
      hidden.value = JSON.stringify(selectedIds);
    }

    function renderSelected() {
      if (selectedIds.length === 0) {
        selectedBox.textContent = "Ничего не выбрано";
        return;
      }
      selectedBox.innerHTML = "";
      for (const id of selectedIds) {
        const product = byId.get(id);
        const chip = document.createElement("button");
        chip.type = "button";
        chip.className = "admin-product-picker__chip";
        chip.dataset.removeProductId = id;
        chip.textContent = product ? `${product.name || "Товар"} ×` : `${id} ×`;
        selectedBox.appendChild(chip);
      }
    }

    function renderList() {
      const q = search.value.trim().toLowerCase();
      const selectedSet = new Set(selectedIds);
      const filtered = products.filter((p) => {
        if (!q) {
          return true;
        }
        return [p.name, p.article, p.slug].some((v) => String(v || "").toLowerCase().includes(q));
      });
      filtered.sort((a, b) => {
        const ai = selectedIds.indexOf(a.id);
        const bi = selectedIds.indexOf(b.id);
        if (ai !== -1 || bi !== -1) {
          if (ai === -1) return 1;
          if (bi === -1) return -1;
          return ai - bi;
        }
        return String(a.name || "").localeCompare(String(b.name || ""), "ru");
      });

      list.innerHTML = "";
      const shown = filtered.slice(0, 160);
      if (shown.length === 0) {
        list.textContent = "Товары не найдены";
        return;
      }
      for (const p of shown) {
        const row = document.createElement("label");
        row.className = "admin-product-picker__row";
        const cb = document.createElement("input");
        cb.type = "checkbox";
        cb.value = p.id;
        cb.checked = selectedSet.has(p.id);
        const text = document.createElement("span");
        text.innerHTML = `<strong>${escapeHtml(p.name || "Товар")}</strong><small>${escapeHtml([p.article, p.slug].filter(Boolean).join(" · "))}</small>`;
        row.appendChild(cb);
        row.appendChild(text);
        list.appendChild(row);
      }
      if (filtered.length > shown.length) {
        const more = document.createElement("p");
        more.className = "muted small admin-product-picker__more";
        more.textContent = `Показано ${shown.length} из ${filtered.length}. Уточните поиск.`;
        list.appendChild(more);
      }
    }

    function render() {
      renderSelected();
      renderList();
      syncHidden();
    }

    search.disabled = false;
    search.addEventListener("input", renderList);
    list.addEventListener("change", (ev) => {
      const target = ev.target;
      if (!(target instanceof HTMLInputElement) || target.type !== "checkbox") {
        return;
      }
      const id = target.value;
      if (target.checked) {
        if (!selectedIds.includes(id)) {
          selectedIds.push(id);
        }
      } else {
        selectedIds = selectedIds.filter((x) => x !== id);
      }
      render();
    });
    selectedBox.addEventListener("click", (ev) => {
      const target = ev.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }
      const id = target.dataset.removeProductId;
      if (!id) {
        return;
      }
      selectedIds = selectedIds.filter((x) => x !== id);
      render();
    });

    render();
  } catch (e) {
    selectedBox.textContent = "Не удалось загрузить товары";
    list.textContent = e?.message || String(e);
  }
}

async function populateCategoryParentSelect(form, entity) {
  const sel = form.elements.parent_id;
  if (!(sel instanceof HTMLSelectElement)) {
    return;
  }
  try {
    const data = await apiFetch("/categories?itemsPerPage=500", { headers: API_ACCEPT_JSONLD });
    const { items } = unwrapCollection(data);
    const excludeIds = entity?.id ? getSelfAndDescendantIds(items, entity.id) : new Set();
    const choices = items
      .filter((c) => !excludeIds.has(c.id))
      .filter((c) => c.display_mode !== "products_only")
      .sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), "ru"))
      .map((c) => ({
        id: c.id,
        label: `${c.name} (${c.slug})`,
      }));
    sel.innerHTML = "";
    const rootOpt = document.createElement("option");
    rootOpt.value = "";
    rootOpt.textContent = "— Корень (без родителя) —";
    sel.appendChild(rootOpt);
    for (const row of choices) {
      const o = document.createElement("option");
      o.value = row.id;
      o.textContent = row.label;
      sel.appendChild(o);
    }
    const p = entity?.parent_id;
    const want = p != null && p !== "" ? String(p) : "";
    sel.value = want;
    if (want && sel.value !== want) {
      const miss = document.createElement("option");
      miss.value = want;
      miss.textContent = `${want} (не в списке)`;
      miss.selected = true;
      sel.appendChild(miss);
    }
    sel.disabled = false;
  } catch (e) {
    sel.innerHTML = "";
    const err = document.createElement("option");
    err.value = "";
    err.textContent = `Ошибка загрузки: ${e.message}`;
    sel.appendChild(err);
    sel.disabled = true;
  }
}

async function openModal(schemaKey, id) {
  const sec = SECTIONS.find((s) => s.id === schemaKey);
  const schema = CRUD_SCHEMA[schemaKey];
  const isCreate = !id;
  const modal = document.getElementById("modal");
  const form = document.getElementById("modal-form");
  const title = document.getElementById("modal-title");
  const save = document.getElementById("modal-save");
  const modalError = document.getElementById("modal-error");
  form.innerHTML = "";
  form.className = "modal-body";
  modal.classList.toggle("modal--product", schemaKey === "products");
  if (schema?.formClass) {
    form.classList.add(`${schema.formClass}-modal`);
  }
  if (modalError) {
    modalError.textContent = "";
    modalError.classList.add("hidden");
  }
  if (schemaKey === "products") {
    title.textContent = isCreate ? "Новый товар" : "Редактирование товара";
    if (save) {
      save.textContent = isCreate ? "Создать товар" : "Сохранить товар";
    }
  } else {
    title.textContent = isCreate ? "Создание" : "Редактирование";
    if (save) {
      save.textContent = "Сохранить";
    }
  }

  let entity = null;
  if (!isCreate) {
    try {
      entity = await apiFetch(`${sec.resource}/${id}`);
    } catch (e) {
      notifyApiError(e);
      return;
    }
  }

  form.appendChild(buildFormFields(schemaKey, entity, isCreate));
  if (schemaKey === "categories") {
    await populateCategoryParentSelect(form, entity);
    await populateCategoryRecommendationsPicker(form, entity);
  }
  if (schemaKey === "products") {
    await populateProductCategorySelect(form, entity);
  }
  if (schemaKey === "categories" || schemaKey === "products") {
    setupCategoryProductSlugAndPhotoAlt(form, isCreate, entity);
  }
  modal.classList.remove("hidden");
  modal.setAttribute("aria-hidden", "false");

  form.onsubmit = async (ev) => {
    ev.preventDefault();
    if (modalError) {
      modalError.textContent = "";
      modalError.classList.add("hidden");
    }
    const saveText = save?.textContent || "Сохранить";
    if (save) {
      save.disabled = true;
      save.textContent = "Сохраняем…";
    }
    try {
      const payload = readFormPayload(form, schemaKey, isCreate);
      if (isCreate) {
        await apiFetch(sec.resource, { method: "POST", body: JSON.stringify(payload) });
      } else {
        await apiFetch(`${sec.resource}/${id}`, { method: "PUT", body: JSON.stringify(payload) });
      }
      modal.classList.add("hidden");
      loadCrudTable();
    } catch (e) {
      if (e && e.sessionEnded) {
        return;
      }
      if (modalError) {
        modalError.textContent = e?.message || String(e);
        modalError.classList.remove("hidden");
      } else {
        notifyApiError(e);
      }
    } finally {
      if (save) {
        save.disabled = false;
        save.textContent = saveText;
      }
    }
  };
}

function closeModal() {
  const modal = document.getElementById("modal");
  modal.classList.add("hidden");
  modal.setAttribute("aria-hidden", "true");
}

const DEFAULT_FILTER_CONFIG_JSON = '{\n  "keys": [],\n  "labels": {}\n}';

/** Подсказки для системных ключей (остальные — как в technical_specs) */
const CF_KEY_HINTS = {
  has_verification: "Поверка",
};

let cfSelectPopulated = false;
let cfDiscoverBound = false;
/** @type {string[]} */
let cfDiscoveredKeys = [];
/** @type {{ keys: string[], labels: Record<string, string> }} */
let cfFilterState = { keys: [], labels: {} };

function cfNormalizeFilterConfig(fc) {
  if (fc == null) {
    return { keys: [], labels: {} };
  }
  const keys = Array.isArray(fc.keys) ? fc.keys.filter((k) => typeof k === "string" && k !== "") : [];
  const rawLabels =
    fc.labels && typeof fc.labels === "object" && !Array.isArray(fc.labels) ? fc.labels : {};
  const labels = {};
  for (const [k, v] of Object.entries(rawLabels)) {
    if (typeof k === "string" && typeof v === "string" && v.trim() !== "") {
      labels[k] = v;
    }
  }
  return { keys, labels };
}

function cfKeyTitle(key) {
  return CF_KEY_HINTS[key] || key;
}

function cfPayloadForSave() {
  const keys = [...cfFilterState.keys];
  const labels = {};
  for (const [k, v] of Object.entries(cfFilterState.labels)) {
    if (typeof v === "string" && v.trim() !== "") {
      labels[k] = v.trim();
    }
  }
  if (keys.length === 0 && Object.keys(labels).length === 0) {
    return null;
  }
  return { keys, labels };
}

function cfSyncTextareaFromState() {
  const ta = document.getElementById("cf-config");
  if (!(ta instanceof HTMLTextAreaElement)) {
    return;
  }
  const payload = cfPayloadForSave();
  ta.value = payload == null ? DEFAULT_FILTER_CONFIG_JSON : JSON.stringify(payload, null, 2);
}

function cfMoveKey(key, delta) {
  const arr = cfFilterState.keys;
  const i = arr.indexOf(key);
  const j = i + delta;
  if (i < 0 || j < 0 || j >= arr.length) {
    return;
  }
  [arr[i], arr[j]] = [arr[j], arr[i]];
  cfRenderKeyList();
  cfSyncTextareaFromState();
  cfPopulateAddSelect();
}

function cfRemoveKey(key) {
  cfFilterState.keys = cfFilterState.keys.filter((k) => k !== key);
  delete cfFilterState.labels[key];
  cfRenderKeyList();
  cfSyncTextareaFromState();
  cfPopulateAddSelect();
}

function cfAddKey(key) {
  if (!key || cfFilterState.keys.includes(key)) {
    return;
  }
  cfFilterState.keys.push(key);
  cfRenderKeyList();
  cfSyncTextareaFromState();
  cfPopulateAddSelect();
}

function cfRenderKeyList() {
  const list = document.getElementById("cf-key-list");
  if (!list) {
    return;
  }
  list.innerHTML = "";
  const keys = cfFilterState.keys;
  if (keys.length === 0) {
    const p = document.createElement("p");
    p.className = "cf-empty muted small";
    p.textContent =
      "Список пуст — на витрине показываются все фильтры, найденные по товарам (в рамках выбранного режима агрегации).";
    list.appendChild(p);
    return;
  }
  keys.forEach((key, index) => {
    const row = document.createElement("div");
    row.className = "cf-key-row";
    row.dataset.key = key;

    const actions = document.createElement("div");
    actions.className = "cf-key-row-actions";
    const up = document.createElement("button");
    up.type = "button";
    up.className = "btn btn-sm";
    up.textContent = "↑";
    up.disabled = index === 0;
    up.addEventListener("click", () => cfMoveKey(key, -1));
    const down = document.createElement("button");
    down.type = "button";
    down.className = "btn btn-sm";
    down.textContent = "↓";
    down.disabled = index === keys.length - 1;
    down.addEventListener("click", () => cfMoveKey(key, 1));
    actions.appendChild(up);
    actions.appendChild(down);
    row.appendChild(actions);

    const meta = document.createElement("div");
    meta.className = "cf-key-meta";
    const code = document.createElement("code");
    code.className = "cf-key-code";
    code.textContent = key;
    const hint = document.createElement("span");
    hint.className = "muted small";
    hint.textContent = cfKeyTitle(key);
    meta.appendChild(code);
    meta.appendChild(hint);
    row.appendChild(meta);

    const labWrap = document.createElement("label");
    labWrap.className = "cf-key-label";
    labWrap.textContent = "Подпись на витрине";
    const inp = document.createElement("input");
    inp.type = "text";
    inp.className = "cf-label-input";
    inp.dataset.key = key;
    inp.value = cfFilterState.labels[key] ?? "";
    inp.placeholder = cfKeyTitle(key);
    labWrap.appendChild(inp);
    row.appendChild(labWrap);

    const rm = document.createElement("button");
    rm.type = "button";
    rm.className = "btn btn-ghost cf-remove-key";
    rm.textContent = "✕";
    rm.title = "Убрать из списка";
    rm.addEventListener("click", () => cfRemoveKey(key));
    row.appendChild(rm);

    list.appendChild(row);
  });
}

function cfPopulateAddSelect() {
  const sel = document.getElementById("cf-add-key");
  if (!(sel instanceof HTMLSelectElement)) {
    return;
  }
  const used = new Set(cfFilterState.keys);
  sel.innerHTML = "";
  const ph = document.createElement("option");
  ph.value = "";
  ph.textContent = "— выберите ключ —";
  sel.appendChild(ph);
  for (const key of cfDiscoveredKeys) {
    if (used.has(key)) {
      continue;
    }
    const o = document.createElement("option");
    o.value = key;
    o.textContent = `${key} (${cfKeyTitle(key)})`;
    sel.appendChild(o);
  }
}

async function cfRefreshDiscover() {
  const sel = document.getElementById("cf-category");
  const addSel = document.getElementById("cf-add-key");
  const opt = sel?.selectedOptions?.[0];
  const slug = opt?.dataset?.slug;
  const aggEl = document.getElementById("cf-aggregate");
  const aggregate = aggEl instanceof HTMLInputElement && aggEl.checked;
  if (!slug) {
    cfDiscoveredKeys = [];
    cfPopulateAddSelect();
    return;
  }
  if (addSel instanceof HTMLSelectElement) {
    const ph = document.createElement("option");
    ph.value = "";
    ph.textContent = "Загрузка…";
    addSel.innerHTML = "";
    addSel.appendChild(ph);
    addSel.disabled = true;
  }
  const errBox = document.getElementById("cf-error");
  try {
    const res = await apiFetch(
      `/categories/${encodeURIComponent(slug)}/filters/discover?aggregate=${aggregate ? "true" : "false"}`,
    );
    cfDiscoveredKeys = Array.isArray(res.keys) ? res.keys.filter((k) => typeof k === "string") : [];
    if (errBox) {
      errBox.classList.add("hidden");
    }
  } catch (e) {
    cfDiscoveredKeys = [];
    if (!e.sessionEnded && errBox) {
      errBox.textContent = `Ключи фильтров: ${e.message}`;
      errBox.classList.remove("hidden");
    }
  }
  if (addSel instanceof HTMLSelectElement) {
    addSel.disabled = false;
  }
  cfPopulateAddSelect();
}

function cfApplyJsonFromTextarea() {
  const err = document.getElementById("cf-error");
  if (err) {
    err.classList.add("hidden");
  }
  const ta = document.getElementById("cf-config");
  if (!(ta instanceof HTMLTextAreaElement)) {
    return;
  }
  try {
    const raw = ta.value.trim();
    if (raw === "") {
      cfFilterState = { keys: [], labels: {} };
    } else {
      const parsed = JSON.parse(raw);
      cfFilterState = cfNormalizeFilterConfig(parsed);
    }
    cfRenderKeyList();
    cfPopulateAddSelect();
  } catch (e) {
    if (err) {
      err.textContent = e.message || String(e);
      err.classList.remove("hidden");
    }
  }
}

function cfBindFilterEditorEvents() {
  const list = document.getElementById("cf-key-list");
  if (list && !list.dataset.cfDelegation) {
    list.dataset.cfDelegation = "1";
    list.addEventListener("input", (e) => {
      const t = e.target;
      if (!(t instanceof HTMLInputElement) || !t.classList.contains("cf-label-input")) {
        return;
      }
      const key = t.dataset.key;
      if (!key) {
        return;
      }
      const v = t.value.trim();
      if (v === "") {
        delete cfFilterState.labels[key];
      } else {
        cfFilterState.labels[key] = t.value;
      }
      cfSyncTextareaFromState();
    });
  }
}

const SITE_CONTACTS_FIELDS = [
  { key: "phone_main_href", label: "Телефон основной (ссылка tel:)", placeholder: "tel:+78432023170" },
  { key: "phone_main_label", label: "Телефон основной (как показать)", placeholder: "+7 (843) 202-31-70" },
  { key: "phone_extra_href", label: "Телефон дополнительный (tel:)", placeholder: "tel:+79272495218" },
  { key: "phone_extra_label", label: "Телефон дополнительный (текст)", placeholder: "+7 927-249-52-18" },
  { key: "email_sales", label: "E-mail отдела продаж", placeholder: "sales@example.com" },
  { key: "email_metrology", label: "E-mail отдела метрологии", placeholder: "metrology@example.com" },
  { key: "messenger_vk", label: "ВКонтакте (URL)", placeholder: "https://vk.com/..." },
  { key: "messenger_telegram", label: "Telegram (URL)", placeholder: "https://t.me/..." },
  { key: "messenger_whatsapp", label: "WhatsApp (URL)", placeholder: "https://wa.me/..." },
  { key: "messenger_max", label: "Max (URL)", placeholder: "https://max.ru/..." },
  {
    key: "map_iframe_src",
    label: "Карта: URL для iframe",
    placeholder:
      "https://yandex.ru/map-widget/v1/?ll=49.276146%2C55.912513&z=18&pt=49.275821%2C55.912601%2Cpm2rdm",
  },
  {
    key: "map_yandex_link",
    label: "Карта: ссылка «Открыть в Яндекс»",
    placeholder:
      "https://yandex.ru/maps/?from=mapframe&ll=49.276146%2C55.912513&mode=poi&poi%5Bpoint%5D=49.275821%2C55.912601&poi%5Buri%5D=ymapsbm1%3A%2F%2Forg%3Foid%3D92963604301&source=mapframe&utm_source=mapframe&z=17.59",
  },
];

function ensureSiteContactsFormBuilt() {
  const wrap = document.getElementById("sc-fields");
  if (!wrap || wrap.dataset.built) {
    return;
  }
  wrap.dataset.built = "1";
  wrap.innerHTML = SITE_CONTACTS_FIELDS.map((f) => {
    const ph = f.placeholder ? ` placeholder="${escapeHtml(f.placeholder)}"` : "";
    return `<label class="sc-label">${escapeHtml(f.label)}
        <input type="text" name="${escapeHtml(f.key)}" autocomplete="off"${ph} class="sc-input" />
      </label>`;
  }).join("");
}

function formatVisitInt(n) {
  return new Intl.NumberFormat("ru-RU").format(Number(n) || 0);
}

const dashMoneyFmt = new Intl.NumberFormat("ru-RU", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

function formatDashMoneyRu(s) {
  const n = parseFloat(String(s ?? "").replace(",", "."));
  if (Number.isNaN(n)) {
    return "—";
  }
  return `${dashMoneyFmt.format(n)} ₽`;
}

function orderItemTypeRu(type) {
  if (type === "service") return "Услуга";
  if (type === "product") return "Товар";
  return type ? String(type) : "—";
}

function parseOrderMoneyNumber(v) {
  if (v === null || v === undefined || v === "") return null;
  const n = typeof v === "number" ? v : parseFloat(String(v).replace(",", "."));
  return Number.isFinite(n) ? n : null;
}

/**
 * Таблица позиций заявки (товары/услуги, кол-во, суммы).
 * @param {unknown} items
 */
function renderOrderItemsTableHtml(items) {
  if (!Array.isArray(items) || items.length === 0) {
    return '<p class="muted">Нет позиций</p>';
  }
  let sumLines = 0;
  const rows = items
    .map((raw) => {
      const it = raw && typeof raw === "object" ? raw : {};
      const name = it.name != null ? String(it.name) : "—";
      const articleRaw = it.article != null && String(it.article).trim() !== "" ? String(it.article) : "";
      const article = articleRaw || "—";
      const typeLabel = orderItemTypeRu(it.type);
      const qty = Math.max(0, parseInt(String(it.quantity ?? 0), 10) || 0);
      const priceNum = parseOrderMoneyNumber(it.price);
      const unitStr = priceNum != null ? `${dashMoneyFmt.format(priceNum)} ₽` : "—";
      const line = (priceNum ?? 0) * qty;
      sumLines += line;
      const lineStr = `${dashMoneyFmt.format(line)} ₽`;
      return `<tr>
        <td>${escapeHtml(name)}</td>
        <td>${articleRaw ? `<code>${escapeHtml(articleRaw)}</code>` : escapeHtml(article)}</td>
        <td>${escapeHtml(typeLabel)}</td>
        <td class="num">${unitStr}</td>
        <td class="num">${new Intl.NumberFormat("ru-RU").format(qty)}</td>
        <td class="num">${lineStr}</td>
      </tr>`;
    })
    .join("");
  return `<div class="table-wrap order-items-preview">
    <table class="data-table order-items-table">
      <thead>
        <tr>
          <th>Наименование</th>
          <th>Артикул</th>
          <th>Тип</th>
          <th class="num">Цена за ед.</th>
          <th class="num">Кол-во</th>
          <th class="num">Сумма</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
      <tfoot>
        <tr>
          <td colspan="5"><strong>Итого по позициям</strong></td>
          <td class="num"><strong>${dashMoneyFmt.format(sumLines)} ₽</strong></td>
        </tr>
      </tfoot>
    </table>
  </div>`;
}

function formatDashInt(n) {
  return new Intl.NumberFormat("ru-RU").format(Number(n) || 0);
}

/**
 * @param {HTMLElement} container
 * @param {Array<{ label: string, shortLabel?: string, value: number, display?: string }>} values
 */
function renderDashBarChart(container, values, options = {}) {
  const { color = "var(--accent)", emptyHint = "Нет данных" } = options;
  if (!values.length) {
    container.innerHTML = `<p class="muted small">${emptyHint}</p>`;
    return;
  }
  const max = Math.max(1, ...values.map((v) => v.value));
  const bars = values
    .map((v) => {
      const h = Math.round((v.value / max) * 100);
      const title = `${v.label}: ${v.display ?? v.value}`;
      return `<div class="dash-bar-col">
        <div class="dash-bar-track" title="${escapeHtml(title)}">
          <div class="dash-bar-fill" style="height:${h}%;background:${color}"></div>
        </div>
        <span class="dash-bar-x">${escapeHtml(v.shortLabel ?? v.label)}</span>
      </div>`;
    })
    .join("");
  container.innerHTML = `<div class="dash-bar-chart">${bars}</div>`;
}

const DASH_DONUT_COLORS = [
  "rgba(61, 139, 253, 0.92)",
  "rgba(139, 192, 142, 0.92)",
  "rgba(232, 176, 108, 0.95)",
  "rgba(186, 120, 220, 0.9)",
  "rgba(94, 200, 214, 0.92)",
  "rgba(232, 130, 130, 0.9)",
  "rgba(200, 200, 100, 0.95)",
  "rgba(160, 165, 230, 0.92)",
];

function dashDonutSegmentPath(cx, cy, rOuter, rInner, startDeg, endDeg) {
  const rad = (deg) => ((deg - 90) * Math.PI) / 180;
  const x0o = cx + rOuter * Math.cos(rad(startDeg));
  const y0o = cy + rOuter * Math.sin(rad(startDeg));
  const x1o = cx + rOuter * Math.cos(rad(endDeg));
  const y1o = cy + rOuter * Math.sin(rad(endDeg));
  const x1i = cx + rInner * Math.cos(rad(endDeg));
  const y1i = cy + rInner * Math.sin(rad(endDeg));
  const x0i = cx + rInner * Math.cos(rad(startDeg));
  const y0i = cy + rInner * Math.sin(rad(startDeg));
  const delta = endDeg - startDeg;
  const large = delta > 180 ? 1 : 0;
  return `M ${x0o} ${y0o} A ${rOuter} ${rOuter} 0 ${large} 1 ${x1o} ${y1o} L ${x1i} ${y1i} A ${rInner} ${rInner} 0 ${large} 0 ${x0i} ${y0i} Z`;
}

/**
 * @param {HTMLElement} container
 * @param {{ total_visits?: number, segments?: Array<{ path: string, label?: string, count: number, percent?: number }> }} pathShare
 */
function renderDashDonutChart(container, pathShare) {
  const total = Number(pathShare?.total_visits ?? 0);
  const raw = Array.isArray(pathShare?.segments) ? pathShare.segments : [];
  const segments = raw.filter((s) => s && Number(s.count) > 0);
  if (!total || !segments.length) {
    container.innerHTML = '<p class="muted small">Нет данных о просмотрах за период</p>';
    return;
  }

  const cx = 100;
  const cy = 100;
  const rOuter = 88;
  const rInner = 52;

  const displayLabel = (s) => {
    if (s.path === "__other__") {
      return s.label || "Прочее";
    }
    const p = String(s.path || "/");
    return p.length > 42 ? `${p.slice(0, 40)}…` : p;
  };

  const colorAt = (i, s) => {
    if (s.path === "__other__") {
      return "rgba(139, 156, 176, 0.85)";
    }
    return DASH_DONUT_COLORS[i % DASH_DONUT_COLORS.length];
  };

  let angle = 0;
  const paths = [];
  for (let i = 0; i < segments.length; i += 1) {
    const s = segments[i];
    const cnt = Number(s.count) || 0;
    const sweep = (cnt / total) * 360;
    const start = angle;
    const end = angle + sweep;
    angle = end;
    if (sweep < 0.02) {
      continue;
    }
    const col = colorAt(paths.length, s);
    const d =
      segments.length === 1 && sweep >= 359.5
        ? null
        : dashDonutSegmentPath(cx, cy, rOuter, rInner, start, end);
    paths.push({ d, col, s, sweep });
  }

  let svgInner;
  if (segments.length === 1 && paths.length === 1 && paths[0].sweep >= 359.5) {
    const rMid = (rOuter + rInner) / 2;
    const sw = rOuter - rInner;
    const col = colorAt(0, segments[0]);
    const full = `${displayLabel(segments[0])} — ${formatDashInt(segments[0].count)} (${segments[0].percent ?? "—"}%)`;
    svgInner = `<circle cx="${cx}" cy="${cy}" r="${rMid}" fill="none" stroke="${col}" stroke-width="${sw}"><title>${escapeHtml(full)}</title></circle>`;
  } else {
    svgInner = paths
      .filter((p) => p.d)
      .map((p) => {
        const full = `${displayLabel(p.s)} — ${formatDashInt(p.s.count)} (${p.s.percent ?? "—"}%)`;
        return `<path fill="${p.col}" d="${p.d}" stroke="#0f1419" stroke-width="0.5"><title>${escapeHtml(full)}</title></path>`;
      })
      .join("");
  }

  const legend = segments
    .map((s, i) => {
      const fullPath = s.path === "__other__" ? (s.label || "Прочее") : String(s.path || "/");
      const lab = fullPath.length > 42 ? `${fullPath.slice(0, 40)}…` : fullPath;
      const pct = s.percent != null ? String(s.percent) : "—";
      const col = colorAt(i, s);
      return `<li class="dash-path-legend-item" title="${escapeHtml(fullPath)}">
        <span class="dash-path-swatch" style="background:${col}"></span>
        <span class="dash-path-legend-text"><code>${escapeHtml(lab)}</code> — ${formatDashInt(s.count)} (${pct}%)</span>
      </li>`;
    })
    .join("");

  container.innerHTML = `<div class="dash-donut-layout" role="img" aria-label="Доля просмотров по страницам">
    <svg class="dash-donut-svg" viewBox="0 0 200 200" width="220" height="220" aria-hidden="true">${svgInner}</svg>
    <ul class="dash-path-legend">${legend}</ul>
  </div>`;
}

async function loadDashboard() {
  const errEl = document.getElementById("dash-error");
  const kpi = document.getElementById("dash-kpi");
  const chartV = document.getElementById("dash-chart-visits");
  const chartO = document.getElementById("dash-chart-orders");
  const chartP = document.getElementById("dash-chart-paths");
  const updated = document.getElementById("dash-updated");
  if (!errEl || !kpi || !chartV || !chartO || !chartP) {
    return;
  }
  errEl.classList.add("hidden");
  kpi.innerHTML = '<p class="muted">Загрузка…</p>';
  chartV.innerHTML = "";
  chartO.innerHTML = "";
  chartP.innerHTML = "";
  if (updated) {
    updated.textContent = "";
  }
  try {
    const data = await apiFetch("/admin/dashboard");
    const dateUtc = data.date_utc ?? "";
    const monthUtc = data.month_utc ?? "";

    kpi.innerHTML = `<div class="dash-kpi">
        <div class="dash-kpi-label">Просмотры за сегодня</div>
        <div class="dash-kpi-value">${formatDashInt(data.visits_today)}</div>
        <div class="dash-kpi-hint">${escapeHtml(dateUtc)} UTC</div>
      </div>
      <div class="dash-kpi">
        <div class="dash-kpi-label">Заявок за сегодня</div>
        <div class="dash-kpi-value">${formatDashInt(data.orders_today)}</div>
        <div class="dash-kpi-hint">новые заказы</div>
      </div>
      <div class="dash-kpi">
        <div class="dash-kpi-label">Сумма за сегодня</div>
        <div class="dash-kpi-value">${formatDashMoneyRu(data.revenue_today)}</div>
        <div class="dash-kpi-hint">по полю «Сумма» в заказе</div>
      </div>
      <div class="dash-kpi">
        <div class="dash-kpi-label">Средний чек (месяц)</div>
        <div class="dash-kpi-value">${data.avg_order_amount_month != null ? formatDashMoneyRu(data.avg_order_amount_month) : "—"}</div>
        <div class="dash-kpi-hint">${formatDashInt(data.orders_month_with_amount ?? 0)} с суммой · ${escapeHtml(monthUtc)}</div>
      </div>
      <div class="dash-kpi">
        <div class="dash-kpi-label">Сумма заказов за месяц</div>
        <div class="dash-kpi-value">${formatDashMoneyRu(data.revenue_month)}</div>
        <div class="dash-kpi-hint">${formatDashInt(data.orders_month)} заявок всего</div>
      </div>`;

    const series = Array.isArray(data.series) ? data.series : [];
    const visitVals = series.map((row) => ({
      label: row.date,
      shortLabel: String(row.date).slice(5),
      value: row.visits,
      display: formatDashInt(row.visits),
    }));
    renderDashBarChart(chartV, visitVals, { color: "rgba(61, 139, 253, 0.88)" });

    const orderVals = series.map((row) => ({
      label: row.date,
      shortLabel: String(row.date).slice(5),
      value: row.orders,
      display: formatDashInt(row.orders),
    }));
    const revVals = series.map((row) => ({
      label: row.date,
      shortLabel: String(row.date).slice(5),
      value: parseFloat(String(row.revenue).replace(",", ".")) || 0,
      display: formatDashMoneyRu(row.revenue),
    }));
    const wrap = document.createElement("div");
    wrap.className = "dash-dual-charts";
    const o1 = document.createElement("div");
    o1.className = "dash-dual-block";
    o1.innerHTML = '<p class="dash-dual-title muted small">Заявки (шт.)</p>';
    const c1 = document.createElement("div");
    o1.appendChild(c1);
    const o2 = document.createElement("div");
    o2.className = "dash-dual-block";
    o2.innerHTML = '<p class="dash-dual-title muted small">Выручка (₽)</p>';
    const c2 = document.createElement("div");
    o2.appendChild(c2);
    wrap.appendChild(o1);
    wrap.appendChild(o2);
    chartO.appendChild(wrap);
    renderDashBarChart(c1, orderVals, { color: "rgba(139, 192, 142, 0.92)" });
    renderDashBarChart(c2, revVals, { color: "rgba(232, 176, 108, 0.95)" });

    renderDashDonutChart(chartP, data.path_share);

    if (updated) {
      const t = new Date();
      updated.textContent = `Обновлено ${t.toLocaleTimeString("ru-RU", { hour: "2-digit", minute: "2-digit" })}`;
    }
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    kpi.innerHTML = "";
    chartV.innerHTML = "";
    chartO.innerHTML = "";
    chartP.innerHTML = "";
    errEl.textContent = e.message || String(e);
    errEl.classList.remove("hidden");
  }
}

async function loadVisitStats() {
  const errEl = document.getElementById("vs-error");
  const summary = document.getElementById("vs-summary");
  const tbodyDays = document.getElementById("vs-tbody-days");
  const tbodyPaths = document.getElementById("vs-tbody-paths");
  const emptyDays = document.getElementById("vs-empty-days");
  const emptyPaths = document.getElementById("vs-empty-paths");
  const daysInput = document.getElementById("vs-days");
  if (
    !errEl ||
    !summary ||
    !tbodyDays ||
    !tbodyPaths ||
    !emptyDays ||
    !emptyPaths ||
    !(daysInput instanceof HTMLInputElement)
  ) {
    return;
  }
  errEl.classList.add("hidden");
  let days = parseInt(String(daysInput.value), 10);
  if (Number.isNaN(days) || days < 1) {
    days = 30;
  }
  if (days > 366) {
    days = 366;
  }
  daysInput.value = String(days);
  tbodyDays.innerHTML = "";
  tbodyPaths.innerHTML = "";
  summary.innerHTML = "<p class=\"muted\">Загрузка…</p>";
  try {
    const data = await apiFetch(`/admin/visit-stats?days=${encodeURIComponent(String(days))}&top=25`);
    const total = formatVisitInt(data.total_in_period ?? 0);
    const period = data.period_days ?? days;
    const first = data.first_record_at
      ? `<br />Первая запись в журнале: <code>${escapeHtml(String(data.first_record_at))}</code> (UTC)`
      : "";
    summary.innerHTML = `<p><strong>Всего просмотров за ${period} дн.:</strong> ${total}</p>${first}`;

    const byDay = Array.isArray(data.by_day) ? data.by_day : [];
    const topPaths = Array.isArray(data.top_paths) ? data.top_paths : [];

    if (byDay.length === 0) {
      emptyDays.classList.remove("hidden");
    } else {
      emptyDays.classList.add("hidden");
      for (const row of byDay) {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td><code>${escapeHtml(String(row.date))}</code></td><td>${formatVisitInt(row.count)}</td>`;
        tbodyDays.appendChild(tr);
      }
    }

    if (topPaths.length === 0) {
      emptyPaths.classList.remove("hidden");
    } else {
      emptyPaths.classList.add("hidden");
      for (const row of topPaths) {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td><code>${escapeHtml(String(row.path))}</code></td><td>${formatVisitInt(row.count)}</td>`;
        tbodyPaths.appendChild(tr);
      }
    }
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    summary.innerHTML = "";
    errEl.textContent = e.message || String(e);
    errEl.classList.remove("hidden");
  }
}

async function loadSiteContactsForm() {
  ensureSiteContactsFormBuilt();
  const form = document.getElementById("form-site-contacts");
  if (!(form instanceof HTMLFormElement)) {
    return;
  }
  const err = document.getElementById("sc-error");
  err.classList.add("hidden");
  try {
    const data = await apiFetch("/admin/site-contacts");
    for (const f of SITE_CONTACTS_FIELDS) {
      const el = form.elements.namedItem(f.key);
      if (el instanceof HTMLInputElement) {
        el.value = data[f.key] ?? "";
      }
    }
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    err.textContent = e.message || String(e);
    err.classList.remove("hidden");
  }
}

function formatBytes(n) {
  if (n == null || Number.isNaN(Number(n))) return "—";
  const v = Number(n);
  if (v < 1024) return `${v} Б`;
  if (v < 1024 * 1024) return `${(v / 1024).toFixed(1)} КБ`;
  return `${(v / (1024 * 1024)).toFixed(1)} МБ`;
}

/** Сертификаты: разделы и PDF на /certificates */
let ccCatalogSnapshot = { groups: [], serverFiles: [] };

function newCcId(prefix) {
  if (typeof crypto !== "undefined" && crypto.randomUUID) {
    return `${prefix}-${crypto.randomUUID()}`;
  }
  return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;
}

function ccDocHref(filename) {
  return `/documents/${encodeURIComponent(filename)}`;
}

function collectCcCatalogFromDom() {
  const root = document.getElementById("cc-groups");
  if (!root) {
    return { groups: [] };
  }
  const groups = [];
  root.querySelectorAll(".cc-group").forEach((card) => {
    const gidEl = card.querySelector(".cc-group-id");
    const gid = gidEl instanceof HTMLInputElement ? gidEl.value.trim() : "";
    const titleInp = card.querySelector(".cc-group-title");
    const title = titleInp instanceof HTMLInputElement ? titleInp.value : "";
    const items = [];
    card.querySelectorAll(".cc-item").forEach((row) => {
      const iidEl = row.querySelector(".cc-item-id");
      const iid = iidEl instanceof HTMLInputElement ? iidEl.value.trim() : "";
      const fnEl = row.querySelector(".cc-item-filename");
      const fn = fnEl ? fnEl.textContent.trim() : "";
      const labelInp = row.querySelector(".cc-item-label");
      let label = null;
      if (labelInp instanceof HTMLInputElement) {
        const t = labelInp.value.trim();
        label = t === "" ? null : t;
      }
      if (fn) {
        items.push({ id: iid || newCcId("i"), filename: fn, label });
      }
    });
    groups.push({ id: gid || newCcId("g"), title, items });
  });
  return { groups };
}

function computeCcMissing(groups, files) {
  const set = new Set(files);
  const m = [];
  for (const g of groups) {
    for (const it of g.items || []) {
      if (it.filename && !set.has(it.filename)) {
        m.push(it.filename);
      }
    }
  }
  return [...new Set(m)];
}

function renderCcPickOptions(serverFiles, usedInGroup) {
  const used = new Set(usedInGroup);
  const opts = ['<option value="">— выберите PDF —</option>'];
  for (const f of serverFiles) {
    if (!used.has(f)) {
      opts.push(`<option value="${escapeHtml(f)}">${escapeHtml(f)}</option>`);
    }
  }
  return opts.join("");
}

function renderCertificatesCatalogEditor(data) {
  const root = document.getElementById("cc-groups");
  const miss = document.getElementById("cc-missing");
  const serverFiles = Array.isArray(data.files) ? data.files : [];
  const missing = Array.isArray(data.missing_files) ? data.missing_files : [];
  if (!root || !miss) {
    return;
  }
  const groups = Array.isArray(data.groups) ? data.groups : [];
  ccCatalogSnapshot = {
    groups: JSON.parse(JSON.stringify(groups)),
    serverFiles: [...serverFiles],
  };

  if (missing.length === 0) {
    miss.classList.add("hidden");
    miss.innerHTML = "";
  } else {
    miss.classList.remove("hidden");
    miss.innerHTML = `<p class="card-title">Файлы не найдены на сервере</p><p class="muted small">Имена есть в каталоге, но в папке <code>documents/</code> таких PDF нет. Загрузите файлы или удалите пункты.</p><ul class="cc-miss-list">${missing.map((f) => `<li><code>${escapeHtml(f)}</code></li>`).join("")}</ul>`;
  }

  root.innerHTML = groups
    .map((g, gi) => {
      const items = Array.isArray(g.items) ? g.items : [];
      const usedInGroup = items.map((it) => it.filename).filter(Boolean);
      const itemRows = items
        .map((it, ii) => {
          const fn = it.filename || "";
          const iid = it.id || newCcId("i");
          const label = it.label == null ? "" : String(it.label);
          return `<li class="cc-item">
            <input type="hidden" class="cc-item-id" value="${escapeHtml(iid)}" />
            <div class="cc-item-main">
              <label class="cc-item-label-wrap">Подпись на карточке
                <input type="text" class="cc-item-label" placeholder="По умолчанию из имени файла" value="${escapeHtml(label)}" />
              </label>
              <div class="cc-item-fileline">
                <code class="cc-item-filename">${escapeHtml(fn)}</code>
                <a class="btn btn-ghost" href="${escapeHtml(ccDocHref(fn))}" target="_blank" rel="noopener">Открыть</a>
                <button type="button" class="btn btn-ghost cc-item-up" data-gi="${gi}" data-ii="${ii}">↑</button>
                <button type="button" class="btn btn-ghost cc-item-down" data-gi="${gi}" data-ii="${ii}">↓</button>
                <button type="button" class="btn btn-ghost cc-item-del" data-gi="${gi}" data-ii="${ii}">Удалить</button>
              </div>
            </div>
          </li>`;
        })
        .join("");
      const gid = g.id || newCcId("g");
      const pickOpts = renderCcPickOptions(serverFiles, usedInGroup);
      return `<div class="cc-group card" data-gi="${gi}">
        <input type="hidden" class="cc-group-id" value="${escapeHtml(gid)}" />
        <div class="cc-group-head">
          <label class="cc-group-title-wrap">Название раздела
            <input type="text" class="cc-group-title" value="${escapeHtml(g.title || "")}" />
          </label>
          <div class="cc-group-actions">
            <button type="button" class="btn btn-ghost cc-g-up" data-gi="${gi}">Раздел ↑</button>
            <button type="button" class="btn btn-ghost cc-g-down" data-gi="${gi}">Раздел ↓</button>
            <button type="button" class="btn btn-ghost cc-g-del" data-gi="${gi}">Удалить раздел</button>
          </div>
        </div>
        <ul class="cc-items">${itemRows}</ul>
        <div class="cc-group-foot">
          <button type="button" class="btn cc-upload" data-gi="${gi}">Загрузить PDF…</button>
          <input type="file" accept="application/pdf,.pdf" class="hidden cc-file" data-gi="${gi}" />
          <label class="cc-pick-wrap">Добавить с сервера
            <select class="cc-pick" data-gi="${gi}">${pickOpts}</select>
          </label>
          <button type="button" class="btn cc-add-pick" data-gi="${gi}">Добавить</button>
        </div>
      </div>`;
    })
    .join("");
}

function renderCcFromGroups(groups) {
  renderCertificatesCatalogEditor({
    groups,
    files: ccCatalogSnapshot.serverFiles || [],
    missing_files: computeCcMissing(groups, ccCatalogSnapshot.serverFiles || []),
  });
}

async function loadCertificatesCatalogSection() {
  const errEl = document.getElementById("cc-error");
  if (errEl) {
    errEl.classList.add("hidden");
    errEl.textContent = "";
  }
  const root = document.getElementById("cc-groups");
  if (root) {
    root.innerHTML = "<p class=\"muted\">Загрузка…</p>";
  }
  try {
    const data = await apiFetch("/admin/certificates-catalog");
    renderCertificatesCatalogEditor(data);
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    if (root) {
      root.innerHTML = "";
    }
    if (errEl) {
      errEl.textContent = e.message || String(e);
      errEl.classList.remove("hidden");
    }
  }
}

/**
 * Если на сервере старый index.html без блока — вставляем разметку, иначе панель не показывается и обработчики не цепляются.
 */
function ensureCertificatesCatalogView() {
  if (document.getElementById("view-certificates-catalog")) {
    return;
  }
  const login = document.getElementById("view-login");
  const main = login?.parentElement;
  if (!main) {
    return;
  }
  const section = document.createElement("section");
  section.id = "view-certificates-catalog";
  section.className = "view hidden";
  section.innerHTML = `<p class="muted intro-text">
          Разделы и PDF на странице <code>/certificates</code>. Файлы сохраняются в каталог <code>documents/</code> рядом с API; ссылки на сайте ведут на <code>/documents/…</code>.
        </p>
        <div id="cc-missing" class="card cc-missing-card hidden" aria-live="polite"></div>
        <div id="cc-groups" class="cc-groups"></div>
        <div class="toolbar cc-toolbar">
          <button type="button" id="cc-add-group" class="btn">Добавить раздел</button>
          <button type="button" id="cc-save" class="btn btn-primary">Сохранить</button>
        </div>
        <p id="cc-error" class="error hidden"></p>`;
  main.insertBefore(section, login);
}

async function loadPriceListSection() {
  const box = document.getElementById("pl-status");
  const errEl = document.getElementById("pl-error");
  const resetBtn = document.getElementById("pl-reset");
  errEl.classList.add("hidden");
  errEl.textContent = "";
  if (!box) return;
  box.innerHTML = "<p class=\"muted\">Загрузка…</p>";
  try {
    const data = await apiFetch("/admin/price-list");
    const path = data.download_path || "/price-list.xlsx";
    const absUrl = path.startsWith("http") ? path : `${window.location.origin}${path}`;
    const when =
      data.updated_at && typeof data.updated_at === "string"
        ? new Date(data.updated_at).toLocaleString("ru-RU", { timeZone: "UTC" }) + " UTC"
        : "—";
    const nameLine = data.original_filename
      ? `<p><strong>Имя при загрузке:</strong> ${escapeHtml(String(data.original_filename))}</p>`
      : "";
    if (data.has_custom) {
      box.innerHTML = `<h2 class="card-title">Текущий файл</h2>
        <p><strong>Источник:</strong> загруженный в админке</p>
        <p><strong>Тип:</strong> .${escapeHtml(String(data.extension || ""))}</p>
        ${nameLine}
        <p><strong>Размер:</strong> ${formatBytes(data.bytes)}</p>
        <p><strong>Обновлён:</strong> ${escapeHtml(when)}</p>
        <p><strong>Ссылка для проверки:</strong> <a href="${escapeHtml(absUrl)}" target="_blank" rel="noopener">${escapeHtml(absUrl)}</a></p>`;
      resetBtn.classList.remove("hidden");
    } else {
      box.innerHTML = `<h2 class="card-title">Текущий файл</h2>
        <p><strong>Источник:</strong> файл по умолчанию из поставки (<code>resources/default/price-list.xlsx</code>)</p>
        <p><strong>Размер:</strong> ${formatBytes(data.bytes)}</p>
        <p><strong>Ссылка для проверки:</strong> <a href="${escapeHtml(absUrl)}" target="_blank" rel="noopener">${escapeHtml(absUrl)}</a></p>
        <p class="muted small">Загрузите файл ниже, чтобы заменить его без деплоя.</p>`;
      resetBtn.classList.add("hidden");
    }
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    box.innerHTML = "";
    errEl.textContent = e.message || String(e);
    errEl.classList.remove("hidden");
  }
  await loadPriceListEditorTable();
}

function renderPriceListEditorPager() {
  const prev = document.getElementById("pl-editor-prev");
  const next = document.getElementById("pl-editor-next");
  const pageEl = document.getElementById("pl-editor-page");
  const st = state.priceListEditor;
  const totalPages = Math.max(1, Math.ceil(st.total / st.itemsPerPage));
  if (prev instanceof HTMLButtonElement) {
    prev.disabled = st.page <= 1;
  }
  if (next instanceof HTMLButtonElement) {
    next.disabled = st.page >= totalPages;
  }
  if (pageEl) {
    pageEl.textContent = `Страница ${st.page} из ${totalPages} (${st.total} поз.)`;
  }
}

function clearPriceListEditorError() {
  const err = document.getElementById("pl-editor-error");
  if (err) {
    err.classList.add("hidden");
    err.textContent = "";
  }
}

function showPriceListEditorError(msg) {
  const err = document.getElementById("pl-editor-error");
  if (err) {
    err.textContent = msg;
    err.classList.remove("hidden");
  }
}

async function loadPriceListEditorTable() {
  const tbody = document.getElementById("pl-editor-tbody");
  const empty = document.getElementById("pl-editor-empty");
  if (!tbody || !empty) {
    return;
  }
  clearPriceListEditorError();
  tbody.innerHTML = "<tr><td colspan=\"3\" class=\"muted\">Загрузка…</td></tr>";
  const st = state.priceListEditor;
  try {
    const q = st.query.trim();
    const buildParams = () => {
      const params = new URLSearchParams();
      params.set("page", String(st.page));
      params.set("itemsPerPage", String(st.itemsPerPage));
      params.set("order[name]", "asc");
      return params;
    };
    let params = buildParams();
    if (q) {
      params.set("name", q);
    }
    let data = await apiFetch(`/products?${params.toString()}`, { headers: API_ACCEPT_JSONLD });
    let { items, total } = unwrapCollection(data);
    if (q && (!Array.isArray(items) || items.length === 0)) {
      params = buildParams();
      params.set("article", q);
      data = await apiFetch(`/products?${params.toString()}`, { headers: API_ACCEPT_JSONLD });
      ({ items, total } = unwrapCollection(data));
    }
    st.total = typeof total === "number" ? total : items.length;
    tbody.innerHTML = "";
    if (items.length === 0) {
      empty.classList.remove("hidden");
    } else {
      empty.classList.add("hidden");
      for (const row of items) {
        const tr = document.createElement("tr");
        const dirty = Object.prototype.hasOwnProperty.call(st.dirtyPrices, row.id);
        const rawPrice = dirty ? st.dirtyPrices[row.id] : row.price ?? "";
        const sourcePrice = row.price ?? "";
        tr.innerHTML = `<td>${escapeHtml(row.name || "—")}</td>
          <td><code>${escapeHtml(row.article || "—")}</code></td>
          <td>
            <input
              type="text"
              class="pl-editor-price-input ${dirty ? "is-dirty" : ""}"
              data-product-id="${escapeHtml(row.id)}"
              data-source-price="${escapeHtml(String(sourcePrice))}"
              value="${escapeHtml(String(rawPrice))}"
              placeholder="Введите цену"
            />
          </td>`;
        tbody.appendChild(tr);
      }
    }
    renderPriceListEditorPager();
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    tbody.innerHTML = "";
    empty.classList.add("hidden");
    showPriceListEditorError(e.message || String(e));
  }
}

function updatePriceListEditorDirtyState(productId, value, sourceValue) {
  const st = state.priceListEditor;
  const current = String(value ?? "").trim();
  const initial = String(sourceValue ?? "").trim();
  if (current === initial) {
    delete st.dirtyPrices[productId];
    return false;
  }
  st.dirtyPrices[productId] = value;
  return true;
}

async function savePriceListEditorChanges() {
  const st = state.priceListEditor;
  const dirtyIds = Object.keys(st.dirtyPrices);
  if (dirtyIds.length === 0) {
    alert("Нет изменений для сохранения.");
    return;
  }
  clearPriceListEditorError();
  const saveBtn = document.getElementById("pl-editor-save");
  if (saveBtn instanceof HTMLButtonElement) {
    saveBtn.disabled = true;
  }
  try {
    for (const id of dirtyIds) {
      const entity = await apiFetch(`/products/${id}`);
      const payload = productWritablePayloadFromEntity(entity);
      payload.price = String(st.dirtyPrices[id] ?? "").trim();
      await apiFetch(`/products/${id}`, { method: "PUT", body: JSON.stringify(payload) });
      delete st.dirtyPrices[id];
    }
    await loadPriceListEditorTable();
    alert("Изменения прайс-листа сохранены.");
  } catch (e) {
    notifyApiError(e);
  } finally {
    if (saveBtn instanceof HTMLButtonElement) {
      saveBtn.disabled = false;
    }
  }
}

async function loadFavoritesTable() {
  const tbody = document.getElementById("favorites-tbody");
  const empty = document.getElementById("favorites-empty");
  const errEl = document.getElementById("favorites-error");
  errEl.classList.add("hidden");
  tbody.innerHTML = "";
  try {
    const data = await apiFetch("/categories?itemsPerPage=500", { headers: API_ACCEPT_JSONLD });
    const { items } = unwrapCollection(data);
    if (items.length === 0) {
      empty.classList.remove("hidden");
      return;
    }
    empty.classList.add("hidden");
    const sorted = [...items].sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), "ru"));
    for (const c of sorted) {
      const tr = document.createElement("tr");
      if (c.is_favorite_main || c.is_favorite_sidebar) {
        tr.classList.add("row-favorite");
      }
      tr.innerHTML = `<td>${escapeHtml(c.name)}</td><td><code>${escapeHtml(c.slug)}</code></td>
        <td class="td-cb"><input type="checkbox" data-cat-id="${escapeHtml(c.id)}" data-field="is_favorite_main" ${c.is_favorite_main ? "checked" : ""} /></td>
        <td class="td-cb"><input type="checkbox" data-cat-id="${escapeHtml(c.id)}" data-field="is_favorite_sidebar" ${c.is_favorite_sidebar ? "checked" : ""} /></td>`;
      tbody.appendChild(tr);
    }
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    errEl.textContent = e.message;
    errEl.classList.remove("hidden");
  }
}

async function saveFavoriteFlags(categoryId, field, value) {
  try {
    const cur = await apiFetch(`/categories/${categoryId}`);
    cur[field] = value;
    await apiFetch(`/categories/${categoryId}`, { method: "PUT", body: JSON.stringify(cur) });
  } catch (e) {
    notifyApiError(e);
    loadFavoritesTable();
  }
}

async function loadCategoryFiltersEditor() {
  const sel = document.getElementById("cf-category");
  const id = sel?.value;
  const err = document.getElementById("cf-error");
  if (err) {
    err.classList.add("hidden");
  }
  if (!id) {
    cfFilterState = { keys: [], labels: {} };
    cfSyncTextareaFromState();
    cfRenderKeyList();
    cfDiscoveredKeys = [];
    cfPopulateAddSelect();
    return;
  }
  try {
    const c = await apiFetch(`/categories/${id}`);
    cfFilterState = cfNormalizeFilterConfig(c.filter_config);
    const aggEl = document.getElementById("cf-aggregate");
    if (aggEl instanceof HTMLInputElement) {
      aggEl.checked = Boolean(c.aggregate_products);
    }
    cfSyncTextareaFromState();
    cfRenderKeyList();
    await cfRefreshDiscover();
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    if (err) {
      err.textContent = e.message;
      err.classList.remove("hidden");
    }
  }
}

/* ——— Photos (media gallery) ——— */

let photosEventsBound = false;

const photosState = {
  ownerType: "product",
  ownerId: "",
  items: [],
  dragId: null,
};

/** Открытие «Фотографии» из таблицы категорий/товаров: { ownerType, ownerId, label } */
let photosPendingOpen = null;

function openPhotosForEntity(ownerType, ownerId, label) {
  photosPendingOpen = {
    ownerType: ownerType === "category" ? "category" : "product",
    ownerId,
    label: label || "",
  };
  goSection("photos");
}

function mediaPublicPath(path) {
  if (!path) return "";
  if (/^https?:\/\//i.test(path)) return path;
  return path.startsWith("/") ? path : `/${path}`;
}

async function apiUploadFormData(relPath, formData) {
  const h = { Accept: "application/json" };
  if (state.token) h.Authorization = `Bearer ${state.token}`;
  const res = await fetch(`${API_BASE}${relPath}`, {
    method: "POST",
    headers: h,
    body: formData,
    credentials: "include",
  });
  const text = await res.text();
  let data = null;
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = text;
    }
  }
  if (!res.ok) {
    if (res.status === 401 && state.token) {
      const authMsg = humanizeJwt401Message(data);
      sessionExpiredToLogin(authMsg);
      const err = new Error(authMsg);
      err.sessionEnded = true;
      throw err;
    }
    const msg =
      typeof data === "object" && data !== null
        ? data.detail || data.message || JSON.stringify(data)
        : data || res.statusText;
    throw new Error(msg);
  }
  return data;
}

async function fetchAllHydraMembers(resourceBase) {
  const all = [];
  let path = `${resourceBase}${resourceBase.includes("?") ? "&" : "?"}itemsPerPage=100`;
  let guard = 0;
  while (path && guard++ < 50) {
    const data = await apiFetch(path.startsWith("/") ? path : `/${path}`, { headers: API_ACCEPT_JSONLD });
    const { items, next } = unwrapCollection(data);
    all.push(...items);
    if (!next) break;
    let np = next;
    try {
      const u = new URL(next, window.location.origin);
      np = u.pathname + u.search;
    } catch {
      const i = String(next).indexOf("/api/");
      if (i !== -1) np = String(next).slice(i + 4);
    }
    np = np.replace(/^\/api/, "");
    path = np.startsWith("/") ? np : `/${np}`;
  }
  return all;
}

async function loadPhotosEntitySelect() {
  const sel = document.getElementById("ph-owner");
  const typeEl = document.getElementById("ph-owner-type");
  if (!sel || !(typeEl instanceof HTMLSelectElement)) return;
  const type = typeEl.value || "product";
  photosState.ownerType = type;
  sel.innerHTML = "<option value=\"\">— Загрузка… —</option>";
  try {
    const base = type === "category" ? "/categories" : "/products";
    const rows = await fetchAllHydraMembers(base);
    sel.innerHTML = "<option value=\"\">— Выберите —</option>";
    rows.sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), "ru"));
    for (const r of rows) {
      const opt = document.createElement("option");
      opt.value = r.id;
      const extra = type === "product" && r.slug ? ` · ${r.slug}` : "";
      opt.textContent = `${r.name || r.id}${extra}`;
      sel.appendChild(opt);
    }
  } catch (e) {
    sel.innerHTML = "<option value=\"\">Ошибка списка</option>";
    notifyApiError(e);
  }
}

function getPhotosOrderedIdsFromDom() {
  const grid = document.getElementById("ph-grid");
  if (!grid) return [];
  return [...grid.querySelectorAll(".ph-card")].map((c) => c.dataset.id).filter(Boolean);
}

async function persistPhotosReorder() {
  await apiFetch("/admin/media/reorder", {
    method: "POST",
    body: JSON.stringify({
      owner_type: photosState.ownerType,
      owner_id: photosState.ownerId,
      ordered_ids: getPhotosOrderedIdsFromDom(),
    }),
  });
}

function renderPhotosGrid() {
  const grid = document.getElementById("ph-grid");
  const empty = document.getElementById("ph-empty");
  if (!grid || !empty) return;
  grid.innerHTML = "";
  const items = photosState.items;
  if (!items.length) {
    empty.classList.remove("hidden");
    empty.textContent = "Нет загруженных фото. Выберите файлы ниже.";
    return;
  }
  empty.classList.add("hidden");
  for (const it of items) {
    const card = document.createElement("div");
    card.className = `ph-card${it.is_primary ? " ph-card--primary" : ""}`;
    card.draggable = true;
    card.dataset.id = it.id;
    const thumb = mediaPublicPath(it.thumb_url);
    const starTitle = it.is_primary ? "Обложка (в карточке первой)" : "Сделать обложкой";
    card.innerHTML = `
      <div class="ph-card__drag" title="Перетащите для порядка">⋮⋮</div>
      <div class="ph-card__img-wrap">
        <img src="${escapeHtml(thumb)}" width="120" height="120" alt="" loading="lazy" decoding="async" />
        ${it.is_primary ? "<span class=\"ph-badge\">Обложка</span>" : ""}
      </div>
      <label class="ph-alt-label">Alt<input type="text" class="ph-alt-input" data-id="${escapeHtml(it.id)}" value="${escapeHtml(it.alt || "")}" /></label>
      <div class="ph-card__actions">
        <button type="button" class="btn btn-ghost ph-star" data-id="${escapeHtml(it.id)}" title="${escapeHtml(starTitle)}">★</button>
        <button type="button" class="btn btn-danger ph-del" data-id="${escapeHtml(it.id)}">Удалить</button>
      </div>`;
    grid.appendChild(card);
  }
}

/** После loadPhotosEntitySelect: выбрать запись и открыть галерею (без повторной загрузки списка). */
async function applyPhotosPendingOpenAfterListLoaded() {
  if (!photosPendingOpen) {
    return;
  }
  const po = photosPendingOpen;
  photosPendingOpen = null;
  const ownerEl = document.getElementById("ph-owner");
  if (!(ownerEl instanceof HTMLSelectElement)) {
    return;
  }
  const hasOpt = [...ownerEl.options].some((o) => o.value === po.ownerId);
  if (!hasOpt) {
    const opt = document.createElement("option");
    opt.value = po.ownerId;
    opt.textContent = po.label || `${po.ownerId.slice(0, 8)}…`;
    ownerEl.appendChild(opt);
  }
  ownerEl.value = po.ownerId;
  await loadPhotosGallery();
}

async function loadPhotosGallery() {
  const err = document.getElementById("ph-error");
  const grid = document.getElementById("ph-grid");
  const empty = document.getElementById("ph-empty");
  const typeEl = document.getElementById("ph-owner-type");
  const ownerEl = document.getElementById("ph-owner");
  if (!grid || !empty || !(typeEl instanceof HTMLSelectElement) || !(ownerEl instanceof HTMLSelectElement)) {
    return;
  }
  err.classList.add("hidden");
  const ot = typeEl.value || "product";
  const oid = ownerEl.value || "";
  if (!oid) {
    grid.innerHTML = "";
    empty.classList.remove("hidden");
    empty.textContent = "Выберите запись в списке.";
    photosState.items = [];
    return;
  }
  photosState.ownerType = ot;
  photosState.ownerId = oid;
  empty.classList.add("hidden");
  grid.innerHTML = "<p class=\"muted\">Загрузка…</p>";
  try {
    const data = await apiFetch(
      `/admin/media?owner_type=${encodeURIComponent(ot)}&owner_id=${encodeURIComponent(oid)}`,
    );
    photosState.items = Array.isArray(data.items) ? data.items : [];
    renderPhotosGrid();
  } catch (e) {
    grid.innerHTML = "";
    photosState.items = [];
    if (!e.sessionEnded) {
      err.textContent = e.message;
      err.classList.remove("hidden");
    }
  }
}

async function initPhotosSection() {
  const grid = document.getElementById("ph-grid");
  if (!grid) return;

  if (!photosEventsBound) {
    photosEventsBound = true;
    grid.addEventListener("dragstart", (e) => {
      const c = e.target.closest(".ph-card");
      if (!c || !grid.contains(c)) return;
      photosState.dragId = c.dataset.id;
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", photosState.dragId);
      c.classList.add("ph-card--dragging");
    });
    grid.addEventListener("dragend", (e) => {
      const c = e.target.closest(".ph-card");
      if (c) c.classList.remove("ph-card--dragging");
      photosState.dragId = null;
    });
    grid.addEventListener("dragover", (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = "move";
    });
    grid.addEventListener("drop", async (e) => {
      e.preventDefault();
      const target = e.target.closest(".ph-card");
      const dragId = photosState.dragId;
      const dragEl = dragId ? grid.querySelector(`.ph-card[data-id="${dragId}"]`) : null;
      if (!target || !dragEl || target === dragEl) return;
      const rect = target.getBoundingClientRect();
      const before = e.clientX < rect.left + rect.width / 2;
      if (before) {
        grid.insertBefore(dragEl, target);
      } else {
        grid.insertBefore(dragEl, target.nextSibling);
      }
      try {
        await persistPhotosReorder();
        await loadPhotosGallery();
      } catch (err) {
        notifyApiError(err);
        await loadPhotosGallery();
      }
    });
    grid.addEventListener("click", async (e) => {
      const star = e.target.closest(".ph-star");
      if (star instanceof HTMLElement && star.dataset.id) {
        e.preventDefault();
        try {
          await apiFetch(`/admin/media/${star.dataset.id}`, {
            method: "PATCH",
            body: JSON.stringify({ is_primary: true }),
          });
          await loadPhotosGallery();
        } catch (err) {
          notifyApiError(err);
        }
        return;
      }
      const del = e.target.closest(".ph-del");
      if (del instanceof HTMLElement && del.dataset.id) {
        if (!confirm("Удалить фото?")) return;
        try {
          await apiFetch(`/admin/media/${del.dataset.id}`, { method: "DELETE" });
          await loadPhotosGallery();
        } catch (err) {
          notifyApiError(err);
        }
      }
    });
    grid.addEventListener("change", async (e) => {
      const inp = e.target.closest(".ph-alt-input");
      if (!(inp instanceof HTMLInputElement) || !inp.dataset.id) return;
      try {
        await apiFetch(`/admin/media/${inp.dataset.id}`, {
          method: "PATCH",
          body: JSON.stringify({ alt: inp.value }),
        });
      } catch (err) {
        notifyApiError(err);
      }
    });

    document.getElementById("ph-owner-type")?.addEventListener("change", () => {
      loadPhotosEntitySelect();
      const g = document.getElementById("ph-grid");
      const em = document.getElementById("ph-empty");
      if (g) g.innerHTML = "";
      if (em) {
        em.classList.remove("hidden");
        em.textContent = "Выберите запись и нажмите «Показать фото».";
      }
      photosState.items = [];
    });
    document.getElementById("ph-reload-entities")?.addEventListener("click", () => loadPhotosEntitySelect());
    document.getElementById("ph-load-gallery")?.addEventListener("click", () => loadPhotosGallery());
    document.getElementById("ph-owner")?.addEventListener("change", () => {
      const o = document.getElementById("ph-owner");
      if (o instanceof HTMLSelectElement && o.value) {
        loadPhotosGallery();
      }
    });
    document.getElementById("ph-upload")?.addEventListener("change", async (e) => {
      const t = e.target;
      if (!(t instanceof HTMLInputElement) || !t.files?.length) return;
      const ownerEl = document.getElementById("ph-owner");
      const typeEl = document.getElementById("ph-owner-type");
      if (!(ownerEl instanceof HTMLSelectElement) || !(typeEl instanceof HTMLSelectElement)) {
        t.value = "";
        return;
      }
      const ot = typeEl.value || photosState.ownerType || "product";
      const oid = ownerEl.value || photosState.ownerId;
      if (!oid) {
        alert("Сначала выберите товар или категорию.");
        t.value = "";
        return;
      }
      photosState.ownerType = ot;
      photosState.ownerId = oid;
      const altEl = document.getElementById("ph-upload-alt");
      const altVal = altEl instanceof HTMLInputElement ? altEl.value.trim() : "";
      const files = [...t.files];
      t.value = "";
      const uploadPath = `/admin/media?owner_type=${encodeURIComponent(ot)}&owner_id=${encodeURIComponent(oid)}`;
      for (const f of files) {
        const fd = new FormData();
        fd.append("file", f);
        fd.append("owner_type", ot);
        fd.append("owner_id", oid);
        if (altVal) fd.append("alt", altVal);
        try {
          await apiUploadFormData(uploadPath, fd);
        } catch (err) {
          notifyApiError(err);
          break;
        }
      }
      await loadPhotosGallery();
    });
  }

  try {
    if (photosPendingOpen) {
      const te = document.getElementById("ph-owner-type");
      if (te instanceof HTMLSelectElement) {
        te.value = photosPendingOpen.ownerType;
      }
    }
    await loadPhotosEntitySelect();
    await applyPhotosPendingOpenAfterListLoaded();
  } catch (e) {
    notifyApiError(e);
  }
}

async function initCategoryFiltersSection() {
  const sel = document.getElementById("cf-category");
  const err = document.getElementById("cf-error");
  if (!sel) {
    return;
  }
  if (err) {
    err.classList.add("hidden");
  }
  cfBindFilterEditorEvents();
  if (!cfDiscoverBound) {
    cfDiscoverBound = true;
    const aggEl = document.getElementById("cf-aggregate");
    if (aggEl instanceof HTMLInputElement) {
      aggEl.addEventListener("change", () => cfRefreshDiscover());
    }
    document.getElementById("cf-add-btn")?.addEventListener("click", () => {
      const s = document.getElementById("cf-add-key");
      if (s instanceof HTMLSelectElement && s.value) {
        cfAddKey(s.value);
        s.value = "";
      }
    });
    document.getElementById("cf-add-custom-btn")?.addEventListener("click", () => {
      const inp = document.getElementById("cf-custom-key");
      if (!(inp instanceof HTMLInputElement)) {
        return;
      }
      const k = inp.value.trim().replace(/\s+/g, "_");
      if (!k) {
        return;
      }
      if (!/^[a-zA-Z0-9_-]+$/.test(k)) {
        alert("Ключ: латиница, цифры, символы _ и -");
        return;
      }
      cfAddKey(k);
      inp.value = "";
    });
    document.getElementById("cf-reset-all")?.addEventListener("click", () => {
      cfFilterState = { keys: [], labels: {} };
      cfRenderKeyList();
      cfSyncTextareaFromState();
      cfPopulateAddSelect();
    });
    document.getElementById("cf-sync-discovered")?.addEventListener("click", () => {
      const nextLabels = {};
      for (const k of cfDiscoveredKeys) {
        if (cfFilterState.labels[k]) {
          nextLabels[k] = cfFilterState.labels[k];
        }
      }
      cfFilterState = { keys: [...cfDiscoveredKeys], labels: nextLabels };
      cfRenderKeyList();
      cfSyncTextareaFromState();
      cfPopulateAddSelect();
    });
    document.getElementById("cf-apply-json")?.addEventListener("click", () => cfApplyJsonFromTextarea());
    document.getElementById("cf-refresh-discover")?.addEventListener("click", () => cfRefreshDiscover());
  }
  if (!cfSelectPopulated) {
    try {
      sel.innerHTML = "";
      const data = await apiFetch("/categories?itemsPerPage=500", { headers: API_ACCEPT_JSONLD });
      const { items } = unwrapCollection(data);
      const sorted = [...items].sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), "ru"));
      for (const c of sorted) {
        const o = document.createElement("option");
        o.value = c.id;
        o.dataset.slug = c.slug;
        o.textContent = `${c.name} (${c.slug})`;
        sel.appendChild(o);
      }
      sel.addEventListener("change", () => loadCategoryFiltersEditor());
      cfSelectPopulated = true;
    } catch (e) {
      if (e.sessionEnded) {
        return;
      }
      if (err) {
        err.textContent = e.message;
        err.classList.remove("hidden");
      }
      return;
    }
  }
  await loadCategoryFiltersEditor();
}

async function saveCategoryFilterConfig() {
  const sel = document.getElementById("cf-category");
  const id = sel?.value;
  const err = document.getElementById("cf-error");
  if (err) {
    err.classList.add("hidden");
  }
  if (!id) {
    if (err) {
      err.textContent = "Выберите категорию";
      err.classList.remove("hidden");
    }
    return;
  }
  const parsed = cfPayloadForSave();
  try {
    const cur = await apiFetch(`/categories/${id}`);
    cur.filter_config = parsed;
    await apiFetch(`/categories/${id}`, { method: "PUT", body: JSON.stringify(cur) });
    alert("Сохранено");
    await cfRefreshDiscover();
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    if (err) {
      err.textContent = e.message;
      err.classList.remove("hidden");
    }
  }
}

state.orderDetailId = null;

async function showOrderDetail(id, orderNumberLabel) {
  state.orderDetailId = id;
  document.getElementById("view-crud").classList.add("hidden");
  document.getElementById("view-order-detail").classList.remove("hidden");
  document.getElementById("page-title").textContent = orderNumberLabel ? `Заказ ${orderNumberLabel}` : "Заказ";

  const box = document.getElementById("order-detail");
  box.textContent = "Загрузка…";
  try {
    const o = await apiFetch(`/orders/${id}`);
    const statusSelect = ["new", "processing", "completed", "cancelled"]
      .map((s) => `<option value="${s}" ${o.status === s ? "selected" : ""}>${s}</option>`)
      .join("");
    box.innerHTML = `
      <div class="toolbar">
        <label>Статус (PATCH /orders/{id}/status)
          <select id="order-status-sel">${statusSelect}</select>
        </label>
        <button type="button" class="btn btn-primary" id="order-status-save">Обновить статус</button>
      </div>
      <dl>
        <dt>ID</dt><dd>${escapeHtml(o.id)}</dd>
        <dt>Номер</dt><dd>${escapeHtml(o.order_number)}</dd>
        <dt>Клиент</dt><dd>${escapeHtml(o.customer_name)}</dd>
        <dt>Компания</dt><dd>${escapeHtml(o.customer_company || "—")}</dd>
        <dt>ИНН</dt><dd>${escapeHtml(o.customer_inn || "—")}</dd>
        <dt>Телефон</dt><dd>${escapeHtml(o.customer_phone)}</dd>
        <dt>Email</dt><dd>${escapeHtml(o.customer_email)}</dd>
        <dt>Сумма</dt><dd>${escapeHtml(formatDashMoneyRu(o.total_amount))}</dd>
        <dt>Создан</dt><dd>${escapeHtml(o.created_at)}</dd>
        <dt>Обновлён</dt><dd>${escapeHtml(o.updated_at)}</dd>
      </dl>
      <h3>Комментарий</h3>
      <p>${escapeHtml(o.comment || "—")}</p>
      <h3>Позиции</h3>
      ${renderOrderItemsTableHtml(o.items)}
      <h3>Вложения</h3>
      <pre>${escapeHtml(JSON.stringify(o.attachments ?? [], null, 2))}</pre>
      <p class="muted">Полное редактирование — кнопка «Изменить» в списке заказов (PUT /api/orders/{id}).</p>
    `;
    document.getElementById("order-status-save").addEventListener("click", async () => {
      const sel = document.getElementById("order-status-sel");
      try {
        await apiFetch(`/orders/${id}/status`, {
          method: "PATCH",
          body: JSON.stringify({ status: sel.value }),
        });
        alert("Статус обновлён");
        showOrderDetail(id, o.order_number);
      } catch (e) {
        notifyApiError(e);
      }
    });
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    box.textContent = e.message;
  }
}

document.getElementById("btn-order-back").addEventListener("click", () => {
  state.orderDetailId = null;
  document.getElementById("view-order-detail").classList.add("hidden");
  document.getElementById("view-crud").classList.remove("hidden");
  document.getElementById("page-title").textContent = "Заказы";
  loadCrudTable();
});

/* ——— API presets ——— */

const API_PRESETS = [
  { id: "tree", name: "GET Дерево категорий", method: "GET", path: "/categories/tree" },
  { id: "fav-main", name: "GET Избранные (главная)", method: "GET", path: "/categories/favorites/main" },
  { id: "fav-side", name: "GET Избранные (сайдбар)", method: "GET", path: "/categories/favorites/sidebar" },
  {
    id: "cat-slug",
    name: "GET Категория по slug",
    method: "GET",
    path: "/categories/by-slug/{slug}",
    params: [{ key: "slug", label: "Slug" }],
  },
  {
    id: "cat-products",
    name: "GET Товары категории",
    method: "GET",
    path: "/categories/{slug}/products",
    params: [{ key: "slug", label: "Slug категории" }],
    query: [
      { key: "page", label: "Страница", default: "1" },
      { key: "limit", label: "Лимит", default: "24" },
    ],
  },
  {
    id: "cat-filters",
    name: "GET Фильтры категории",
    method: "GET",
    path: "/categories/{slug}/filters",
    params: [{ key: "slug", label: "Slug" },
    ],
  },
  { id: "prod-pop", name: "GET Популярные товары", method: "GET", path: "/products/popular" },
  { id: "prod-new", name: "GET Новинки", method: "GET", path: "/products/new" },
  {
    id: "prod-slug",
    name: "GET Товар по slug",
    method: "GET",
    path: "/products/by-slug/{slug}",
    params: [{ key: "slug", label: "Slug товара" }],
  },
  {
    id: "prod-art",
    name: "GET Товары по артикулам",
    method: "GET",
    path: "/products/by-articles",
    query: [{ key: "articles", label: "Артикулы через запятую", default: "" }],
  },
  {
    id: "search",
    name: "GET Поиск",
    method: "GET",
    path: "/search",
    query: [
      { key: "q", label: "Запрос (от 2 симв.)", default: "мерник" },
      { key: "type", label: "Тип: all|products|categories", default: "all" },
      { key: "page", label: "Страница", default: "1" },
      { key: "limit", label: "Лимит", default: "20" },
    ],
  },
  {
    id: "search-ac",
    name: "GET Автодополнение",
    method: "GET",
    path: "/search/autocomplete",
    query: [
      { key: "q", label: "Запрос", default: "ме" },
      { key: "limit", label: "Лимит", default: "10" },
    ],
  },
  {
    id: "seo-meta",
    name: "GET SEO meta",
    method: "GET",
    path: "/seo/{type}/{slug}",
    params: [
      { key: "type", label: "Тип (category|product)", default: "category" },
      { key: "slug", label: "Slug", default: "" },
    ],
  },
  {
    id: "seo-can",
    name: "GET SEO canonical",
    method: "GET",
    path: "/seo/canonical",
    query: [
      { key: "url", label: "URL", default: "" },
      { key: "type", label: "Тип", default: "category" },
    ],
  },
  { id: "cart-get", name: "GET Корзина", method: "GET", path: "/cart" },
  {
    id: "cart-add",
    name: "POST Добавить в корзину",
    method: "POST",
    path: "/cart/items",
    bodyDefault: JSON.stringify({ type: "product", id: "", quantity: 1 }, null, 2),
  },
  {
    id: "cart-upd",
    name: "PATCH Позиция корзины",
    method: "PATCH",
    path: "/cart/items/{item_id}",
    params: [{ key: "item_id", label: "ID позиции" }],
    bodyDefault: JSON.stringify({ quantity: 2 }, null, 2),
  },
  {
    id: "cart-del",
    name: "DELETE Позиция корзины",
    method: "DELETE",
    path: "/cart/items/{item_id}",
    params: [{ key: "item_id", label: "ID позиции" }],
  },
  { id: "cart-clear", name: "DELETE Очистить корзину", method: "DELETE", path: "/cart" },
  {
    id: "cart-checkout",
    name: "POST Оформить заказ из корзины",
    method: "POST",
    path: "/cart/checkout",
    bodyDefault: JSON.stringify(
      {
        customer_name: "Тест",
        customer_company: "ООО Тест",
        customer_phone: "+79001234567",
        customer_email: "test@example.com",
        comment: "",
        attachments: [],
      },
      null,
      2
    ),
  },
  {
    id: "order-create",
    name: "POST Создать заказ (публичная заявка)",
    method: "POST",
    path: "/orders",
    bodyDefault: JSON.stringify(
      {
        customer_name: "Тест",
        customer_company: "ООО Тест",
        customer_phone: "+79001234567",
        customer_email: "test@example.com",
        items: [
          { type: "product", id: "UUID-товара", name: "Товар", article: "А-1", quantity: 1, price: 1000 },
        ],
        comment: "",
        attachments: [],
      },
      null,
      2
    ),
  },
  {
    id: "order-pub-status",
    name: "GET Статус заказа по номеру",
    method: "GET",
    path: "/orders/{order_number}/status",
    params: [{ key: "order_number", label: "Номер заказа" }],
  },
];

function initApiPlayground() {
  const sel = document.getElementById("api-preset");
  sel.innerHTML = API_PRESETS.map((p) => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join("");
  sel.addEventListener("change", renderApiParams);
  renderApiParams();
  document.getElementById("api-run").addEventListener("click", runApiPreset);
}

function renderApiParams() {
  const id = document.getElementById("api-preset").value;
  const p = API_PRESETS.find((x) => x.id === id);
  const box = document.getElementById("api-params");
  const body = document.getElementById("api-body");
  box.innerHTML = "";
  (p.params || []).forEach((param) => {
    const lab = document.createElement("label");
    lab.textContent = param.label;
    const inp = document.createElement("input");
    inp.type = "text";
    inp.dataset.param = "path";
    inp.dataset.key = param.key;
    inp.value = param.default ?? "";
    lab.appendChild(inp);
    box.appendChild(lab);
  });
  (p.query || []).forEach((param) => {
    const lab = document.createElement("label");
    lab.textContent = param.label;
    const inp = document.createElement("input");
    inp.type = "text";
    inp.dataset.param = "query";
    inp.dataset.key = param.key;
    inp.value = param.default ?? "";
    lab.appendChild(inp);
    box.appendChild(lab);
  });
  body.value = p.bodyDefault || "";
}

async function runApiPreset() {
  const id = document.getElementById("api-preset").value;
  const p = API_PRESETS.find((x) => x.id === id);
  const withAuth = document.getElementById("api-with-auth").checked;
  let path = p.path;
  document.querySelectorAll("#api-params input[data-param=path]").forEach((inp) => {
    path = path.replace(`{${inp.dataset.key}}`, encodeURIComponent(inp.value));
  });
  const qs = new URLSearchParams();
  document.querySelectorAll("#api-params input[data-param=query]").forEach((inp) => {
    if (inp.value !== "") qs.set(inp.dataset.key, inp.value);
  });
  const q = qs.toString();
  const urlPath = q ? `${path}?${q}` : path;
  const bodyText = document.getElementById("api-body").value.trim();
  const pre = document.getElementById("api-result");
  pre.textContent = "…";
  try {
    const opts = { method: p.method, skipAuth: !withAuth };
    if (["POST", "PUT", "PATCH"].includes(p.method) && bodyText) {
      opts.body = bodyText;
    }
    const data = await apiFetch(urlPath, opts);
    pre.textContent = typeof data === "string" ? data : JSON.stringify(data, null, 2);
  } catch (e) {
    if (e.sessionEnded) {
      pre.textContent = "";
      return;
    }
    pre.textContent = `Ошибка ${e.status || ""}: ${e.message}`;
  }
}

/* ——— Login & boot ——— */

ensureCertificatesCatalogView();

document.getElementById("form-login").addEventListener("submit", async (ev) => {
  ev.preventDefault();
  const fd = new FormData(ev.target);
  const err = document.getElementById("login-error");
  err.classList.add("hidden");
  try {
    const res = await fetch(`${API_BASE}/auth/login`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        username: fd.get("username"),
        password: fd.get("password"),
      }),
    });
    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message || data.detail || "Ошибка входа");
    }
    if (!data.token) throw new Error("Нет token в ответе");
    setToken(data.token);
    document.getElementById("view-login").classList.add("hidden");
    goSection("dashboard");
  } catch (e) {
    err.textContent = e.message;
    err.classList.remove("hidden");
  }
});

document.getElementById("btn-logout").addEventListener("click", () => {
  setToken("");
  state.orderDetailId = null;
  goSection("login");
  document.getElementById("view-login").classList.remove("hidden");
});

document.getElementById("vs-refresh")?.addEventListener("click", () => {
  void loadVisitStats();
});

document.getElementById("dash-refresh")?.addEventListener("click", () => {
  void loadDashboard();
});

document.getElementById("pl-upload")?.addEventListener("click", async () => {
  const inp = document.getElementById("pl-file");
  const errEl = document.getElementById("pl-error");
  errEl.classList.add("hidden");
  errEl.textContent = "";
  if (!(inp instanceof HTMLInputElement) || !inp.files?.length) {
    errEl.textContent = "Выберите файл (.xlsx или .pdf).";
    errEl.classList.remove("hidden");
    return;
  }
  const fd = new FormData();
  fd.append("file", inp.files[0]);
  try {
    await apiUploadFormData("/admin/price-list", fd);
    inp.value = "";
    await loadPriceListSection();
    alert("Прайс-лист обновлён.");
  } catch (e) {
    notifyApiError(e);
  }
});

document.getElementById("pl-reset")?.addEventListener("click", async () => {
  if (!window.confirm("Удалить загруженный файл и снова использовать прайс из поставки (resources/default/price-list.xlsx)?")) {
    return;
  }
  const errEl = document.getElementById("pl-error");
  errEl.classList.add("hidden");
  try {
    await apiFetch("/admin/price-list", { method: "DELETE" });
    await loadPriceListSection();
  } catch (e) {
    notifyApiError(e);
  }
});

document.getElementById("pl-editor-refresh")?.addEventListener("click", async () => {
  state.priceListEditor.page = 1;
  await loadPriceListEditorTable();
});

document.getElementById("pl-editor-save")?.addEventListener("click", async () => {
  await savePriceListEditorChanges();
});

document.getElementById("pl-editor-prev")?.addEventListener("click", async () => {
  const st = state.priceListEditor;
  if (st.page <= 1) {
    return;
  }
  st.page -= 1;
  await loadPriceListEditorTable();
});

document.getElementById("pl-editor-next")?.addEventListener("click", async () => {
  const st = state.priceListEditor;
  const totalPages = Math.max(1, Math.ceil(st.total / st.itemsPerPage));
  if (st.page >= totalPages) {
    return;
  }
  st.page += 1;
  await loadPriceListEditorTable();
});

document.getElementById("pl-editor-tbody")?.addEventListener("input", (ev) => {
  const t = ev.target;
  if (!(t instanceof HTMLInputElement) || !t.classList.contains("pl-editor-price-input")) {
    return;
  }
  const id = t.dataset.productId;
  if (!id) {
    return;
  }
  const source = t.dataset.sourcePrice ?? "";
  const isDirty = updatePriceListEditorDirtyState(id, t.value, source);
  t.classList.toggle("is-dirty", isDirty);
});

document.getElementById("pl-editor-search")?.addEventListener("input", async (ev) => {
  const t = ev.target;
  if (!(t instanceof HTMLInputElement)) {
    return;
  }
  state.priceListEditor.query = t.value;
  state.priceListEditor.page = 1;
  await loadPriceListEditorTable();
});

document.getElementById("cc-add-group").addEventListener("click", () => {
  const groups = collectCcCatalogFromDom().groups;
  groups.push({ id: newCcId("g"), title: "Новый раздел", items: [] });
  renderCcFromGroups(groups);
});

document.getElementById("cc-save").addEventListener("click", async () => {
  const errEl = document.getElementById("cc-error");
  errEl?.classList.add("hidden");
  const body = collectCcCatalogFromDom();
  try {
    await apiFetch("/admin/certificates-catalog", { method: "PUT", body: JSON.stringify(body) });
    alert("Сохранено");
    await loadCertificatesCatalogSection();
  } catch (e) {
    notifyApiError(e);
  }
});

document.getElementById("view-certificates-catalog").addEventListener("click", (e) => {
  const t = e.target;
  if (!(t instanceof HTMLElement)) return;
  if (t.classList.contains("cc-upload")) {
    const gi = t.dataset.gi;
    const inp = document.querySelector(`#cc-groups input.cc-file[data-gi="${gi}"]`);
    inp?.click();
    return;
  }
  if (t.classList.contains("cc-g-up")) {
    const gi = parseInt(t.dataset.gi ?? "0", 10);
    const groups = collectCcCatalogFromDom().groups;
    if (gi > 0 && gi < groups.length) {
      const a = groups[gi - 1];
      groups[gi - 1] = groups[gi];
      groups[gi] = a;
      renderCcFromGroups(groups);
    }
    return;
  }
  if (t.classList.contains("cc-g-down")) {
    const gi = parseInt(t.dataset.gi ?? "0", 10);
    const groups = collectCcCatalogFromDom().groups;
    if (gi >= 0 && gi < groups.length - 1) {
      const a = groups[gi];
      groups[gi] = groups[gi + 1];
      groups[gi + 1] = a;
      renderCcFromGroups(groups);
    }
    return;
  }
  if (t.classList.contains("cc-g-del")) {
    if (!window.confirm("Удалить этот раздел и все документы в нём из каталога? Файлы на диске не удаляются.")) {
      return;
    }
    const gi = parseInt(t.dataset.gi ?? "0", 10);
    const groups = collectCcCatalogFromDom().groups;
    groups.splice(gi, 1);
    renderCcFromGroups(groups);
    return;
  }
  if (t.classList.contains("cc-item-up")) {
    const gi = parseInt(t.dataset.gi ?? "0", 10);
    const ii = parseInt(t.dataset.ii ?? "0", 10);
    const groups = collectCcCatalogFromDom().groups;
    const items = groups[gi]?.items;
    if (!items || ii <= 0 || ii >= items.length) return;
    const a = items[ii - 1];
    items[ii - 1] = items[ii];
    items[ii] = a;
    renderCcFromGroups(groups);
    return;
  }
  if (t.classList.contains("cc-item-down")) {
    const gi = parseInt(t.dataset.gi ?? "0", 10);
    const ii = parseInt(t.dataset.ii ?? "0", 10);
    const groups = collectCcCatalogFromDom().groups;
    const items = groups[gi]?.items;
    if (!items || ii < 0 || ii >= items.length - 1) return;
    const a = items[ii];
    items[ii] = items[ii + 1];
    items[ii + 1] = a;
    renderCcFromGroups(groups);
    return;
  }
  if (t.classList.contains("cc-item-del")) {
    const gi = parseInt(t.dataset.gi ?? "0", 10);
    const ii = parseInt(t.dataset.ii ?? "0", 10);
    const groups = collectCcCatalogFromDom().groups;
    const items = groups[gi]?.items;
    if (!items) return;
    items.splice(ii, 1);
    renderCcFromGroups(groups);
    return;
  }
  if (t.classList.contains("cc-add-pick")) {
    const gi = parseInt(t.dataset.gi ?? "0", 10);
    const sel = document.querySelector(`#cc-groups select.cc-pick[data-gi="${gi}"]`);
    if (!(sel instanceof HTMLSelectElement)) return;
    const fn = sel.value.trim();
    if (!fn) {
      alert("Выберите PDF из списка.");
      return;
    }
    const groups = collectCcCatalogFromDom().groups;
    if (!groups[gi]) return;
    groups[gi].items.push({ id: newCcId("i"), filename: fn, label: null });
    renderCcFromGroups(groups);
  }
});

document.getElementById("view-certificates-catalog").addEventListener("change", async (e) => {
  const t = e.target;
  if (!(t instanceof HTMLInputElement) || !t.classList.contains("cc-file")) return;
  const gi = parseInt(t.dataset.gi ?? "0", 10);
  if (!t.files?.length) return;
  const fd = new FormData();
  fd.append("file", t.files[0]);
  t.value = "";
  try {
    const info = await apiUploadFormData("/admin/certificates-catalog/upload", fd);
    const fn = info.filename;
    if (!fn) throw new Error("Нет имени файла в ответе");
    const groups = collectCcCatalogFromDom().groups;
    if (!groups[gi]) return;
    groups[gi].items.push({ id: newCcId("i"), filename: fn, label: null });
    ccCatalogSnapshot.serverFiles = [...new Set([...(ccCatalogSnapshot.serverFiles || []), fn])].sort((a, b) =>
      a.localeCompare(b, "ru"),
    );
    renderCcFromGroups(groups);
  } catch (err) {
    notifyApiError(err);
  }
});

document.getElementById("form-site-contacts").addEventListener("submit", async (ev) => {
  ev.preventDefault();
  const form = ev.target;
  if (!(form instanceof HTMLFormElement)) {
    return;
  }
  const err = document.getElementById("sc-error");
  err.classList.add("hidden");
  const body = {};
  for (const f of SITE_CONTACTS_FIELDS) {
    body[f.key] = String(form.elements.namedItem(f.key)?.value ?? "").trim();
  }
  try {
    await apiFetch("/admin/site-contacts", { method: "PUT", body: JSON.stringify(body) });
    alert("Сохранено");
  } catch (e) {
    if (e.sessionEnded) {
      return;
    }
    err.textContent = e.message || String(e);
    err.classList.remove("hidden");
  }
});

function initProductsFiltersUi() {
  document.getElementById("pf-apply")?.addEventListener("click", () => {
    readProductsFiltersFromForm();
    state.productsList.page = 1;
    void loadCrudTable();
  });
  document.getElementById("pf-reset")?.addEventListener("click", () => {
    resetProductsListFilters();
    void loadCrudTable();
  });
  document.getElementById("pf-sort")?.addEventListener("change", () => {
    readProductsFiltersFromForm();
    state.productsList.page = 1;
    void loadCrudTable();
  });
  document.getElementById("pf-per-page")?.addEventListener("change", () => {
    readProductsFiltersFromForm();
    state.productsList.page = 1;
    void loadCrudTable();
  });
  document.getElementById("pf-category")?.addEventListener("change", () => {
    readProductsFiltersFromForm();
    state.productsList.page = 1;
    void loadCrudTable();
  });
  document.getElementById("pf-stock")?.addEventListener("change", () => {
    readProductsFiltersFromForm();
    state.productsList.page = 1;
    void loadCrudTable();
  });
  for (const id of ["pf-name", "pf-article", "pf-slug"]) {
    document.getElementById(id)?.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        readProductsFiltersFromForm();
        state.productsList.page = 1;
        void loadCrudTable();
      }
    });
  }
}

document.getElementById("btn-refresh").addEventListener("click", () => void loadCrudTable());
document.getElementById("btn-new").addEventListener("click", () => {
  const sec = SECTIONS.find((s) => s.id === state.section);
  if (sec && sec.type === "crud") openModal(sec.id, null);
});

document.querySelectorAll(".modal [data-close]").forEach((el) => {
  el.addEventListener("click", closeModal);
});

buildNav();
initProductsFiltersUi();
initCategoryTreeDnD();
initApiPlayground();
updateAuthUi();

document.getElementById("favorites-table").addEventListener("change", (e) => {
  const t = e.target;
  if (!(t instanceof HTMLInputElement) || t.type !== "checkbox" || !t.dataset.catId || !t.dataset.field) {
    return;
  }
  saveFavoriteFlags(t.dataset.catId, t.dataset.field, t.checked);
});
document.getElementById("cf-save").addEventListener("click", () => saveCategoryFilterConfig());

if (state.token) {
  document.getElementById("view-login").classList.add("hidden");
  goSection("dashboard");
} else {
  goSection("login");
}
