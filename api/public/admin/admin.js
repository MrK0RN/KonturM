/**
 * Admin UI for KonturM API — same-origin: /admin/ → /api/
 */

const STORAGE_KEY = "konturm_admin_jwt";
const API_BASE = "/api";

const state = {
  token: localStorage.getItem(STORAGE_KEY) || "",
  section: "login",
  collectionUrl: "",
  nextPageUrl: null,
  editing: null,
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

function unwrapCollection(data) {
  if (Array.isArray(data)) return { items: data, total: data.length, next: null };
  if (data && Array.isArray(data["hydra:member"]))
    return {
      items: data["hydra:member"],
      total: data["hydra:totalItems"] ?? data["hydra:member"].length,
      next: data["hydra:view"]?.["hydra:next"] || null,
    };
  if (data && Array.isArray(data.member))
    return { items: data.member, total: data.totalItems ?? data.member.length, next: data.view?.next || null };
  return { items: [], total: 0, next: null };
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
  const loggedIn = Boolean(state.token);
  logout.classList.toggle("hidden", !loggedIn);
  label.textContent = loggedIn ? "Авторизован" : "";
}

/* ——— Navigation ——— */

const SECTIONS = [
  { id: "products", title: "Товары", type: "crud", resource: "/products", admin: true },
  { id: "categories", title: "Категории", type: "crud", resource: "/categories", admin: true },
  { id: "services", title: "Услуги", type: "crud", resource: "/services", admin: true },
  { id: "orders", title: "Заказы", type: "crud", resource: "/orders", admin: true, hideNew: true },
  { id: "api-playground", title: "Конструктор запросов", type: "api", admin: false },
];

function buildNav() {
  const nav = document.getElementById("nav");
  nav.innerHTML = "";
  const g1 = document.createElement("div");
  g1.className = "nav-group";
  g1.textContent = "Каталог и заказы";
  nav.appendChild(g1);
  SECTIONS.filter((s) => s.type === "crud").forEach((s) => {
    const a = document.createElement("a");
    a.href = "#";
    a.dataset.section = s.id;
    a.textContent = s.title;
    nav.appendChild(a);
  });
  const g2 = document.createElement("div");
  g2.className = "nav-group";
  g2.textContent = "Инструменты";
  nav.appendChild(g2);
  const a = document.createElement("a");
  a.href = "#";
  a.dataset.section = "api-playground";
  a.textContent = "Конструктор запросов";
  nav.appendChild(a);

  nav.querySelectorAll("a[data-section]").forEach((el) => {
    el.addEventListener("click", (e) => {
      e.preventDefault();
      goSection(el.dataset.section);
    });
  });
}

function goSection(id) {
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

  if (id === "login") {
    document.getElementById("view-login").classList.remove("hidden");
    title.textContent = "Вход";
    return;
  }
  if (id === "api-playground") {
    document.getElementById("view-api").classList.remove("hidden");
    return;
  }
  state.orderDetailId = null;
  document.getElementById("view-order-detail").classList.add("hidden");
  document.getElementById("view-crud").classList.remove("hidden");
  loadCrudTable();
}

/* ——— CRUD schemas ——— */

const CRUD_SCHEMA = {
  products: {
    listColumns: [
      { key: "name", label: "Название" },
      { key: "slug", label: "Slug" },
      { key: "article", label: "Артикул" },
      { key: "price", label: "Цена" },
      { key: "stock_status", label: "Склад" },
    ],
    fields: [
      { key: "category_id", label: "ID категории", type: "text", required: true },
      { key: "name", label: "Название", type: "text", required: true },
      { key: "slug", label: "Slug", type: "text", required: true },
      { key: "article", label: "Артикул", type: "text" },
      { key: "photo", label: "Фото URL", type: "text" },
      { key: "photo_alt", label: "Alt фото", type: "text" },
      { key: "description", label: "Описание", type: "textarea" },
      { key: "technical_specs", label: "Тех. характеристики (JSON)", type: "json" },
      { key: "price", label: "Цена", type: "text" },
      {
        key: "stock_status",
        label: "Статус наличия",
        type: "select",
        options: [
          { v: "on_order", t: "Под заказ" },
          { v: "in_stock", t: "В наличии" },
        ],
      },
      { key: "manufacturing_time", label: "Срок изготовления", type: "text" },
      { key: "gost_number", label: "ГОСТ", type: "text" },
      { key: "has_verification", label: "Поверка", type: "checkbox" },
      { key: "drawings", label: "Чертежи (JSON)", type: "json" },
      { key: "documents", label: "Документы (JSON)", type: "json" },
      { key: "certificates", label: "Сертификаты (JSON)", type: "json" },
      { key: "meta_title", label: "Meta title", type: "text" },
      { key: "meta_description", label: "Meta description", type: "textarea" },
    ],
  },
  categories: {
    listColumns: [
      { key: "name", label: "Название" },
      { key: "slug", label: "Slug" },
      { key: "sort_order", label: "Порядок" },
      { key: "display_mode", label: "Режим" },
    ],
    fields: [
      { key: "parent_id", label: "ID родителя (пусто = корень)", type: "text" },
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
      { key: "meta_title", label: "Meta title", type: "text" },
      { key: "meta_description", label: "Meta description", type: "textarea" },
    ],
  },
  services: {
    listColumns: [
      { key: "name", label: "Название" },
      { key: "slug", label: "Slug" },
      { key: "price", label: "Цена" },
      { key: "price_type", label: "Тип цены" },
    ],
    fields: [
      { key: "name", label: "Название", type: "text", required: true },
      { key: "slug", label: "Slug", type: "text", required: true },
      { key: "description", label: "Описание", type: "textarea" },
      { key: "price", label: "Цена", type: "text" },
      {
        key: "price_type",
        label: "Тип цены",
        type: "select",
        options: [
          { v: "fixed", t: "Фиксированная" },
          { v: "from", t: "От" },
        ],
      },
      { key: "photo", label: "Фото URL", type: "text" },
      { key: "requires_technical_spec", label: "Нужно ТЗ", type: "checkbox" },
      { key: "meta_title", label: "Meta title", type: "text" },
      { key: "meta_description", label: "Meta description", type: "textarea" },
      { key: "sort_order", label: "Порядок", type: "number" },
    ],
  },
  orders: {
    listColumns: [
      { key: "order_number", label: "№" },
      { key: "customer_name", label: "Клиент" },
      { key: "customer_email", label: "Email" },
      { key: "status", label: "Статус" },
      { key: "created_at", label: "Создан" },
    ],
    fields: [
      { key: "order_number", label: "Номер заказа", type: "text", required: true, editOnly: true },
      { key: "customer_name", label: "Имя", type: "text", required: true },
      { key: "customer_company", label: "Компания", type: "text" },
      { key: "customer_phone", label: "Телефон", type: "text", required: true },
      { key: "customer_email", label: "Email", type: "text", required: true },
      { key: "items", label: "Позиции (JSON)", type: "json", required: true },
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

async function loadCrudTable(url = null) {
  const sec = SECTIONS.find((s) => s.id === state.section);
  if (!sec || sec.type !== "crud") return;
  const schema = CRUD_SCHEMA[sec.id];
  const tbody = document.getElementById("crud-tbody");
  const thead = document.getElementById("crud-thead");
  const empty = document.getElementById("crud-empty");
  const pager = document.getElementById("crud-pager");
  document.getElementById("btn-new").classList.toggle("hidden", Boolean(sec.hideNew));

  const fetchUrl = url || sec.resource;
  state.collectionUrl = sec.resource;

  try {
    const data = await apiFetch(fetchUrl);
    const { items, next } = unwrapCollection(data);
    state.nextPageUrl = next;

    thead.innerHTML = `<tr>${schema.listColumns.map((c) => `<th>${escapeHtml(c.label)}</th>`).join("")}<th class="actions">Действия</th></tr>`;
    tbody.innerHTML = "";
    if (items.length === 0) {
      empty.classList.remove("hidden");
    } else {
      empty.classList.add("hidden");
      for (const row of items) {
        const tr = document.createElement("tr");
        tr.innerHTML =
          schema.listColumns
            .map((c) => `<td>${escapeHtml(formatCell(row[c.key]))}</td>`)
            .join("") +
          `<td class="actions">
            <button type="button" class="btn btn-edit" data-id="${escapeHtml(row.id)}">Изменить</button>
            <button type="button" class="btn btn-danger btn-del" data-id="${escapeHtml(row.id)}">Удалить</button>
          </td>`;
        tbody.appendChild(tr);
      }
      tbody.querySelectorAll(".btn-edit").forEach((b) => {
        b.addEventListener("click", () => openModal(sec.id, b.dataset.id));
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

    pager.classList.remove("hidden");
    pager.innerHTML = "";
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
        loadCrudTable(path);
      });
      pager.appendChild(btn);
    } else if (url) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btn";
      btn.textContent = "К первой странице";
      btn.addEventListener("click", () => loadCrudTable());
      pager.appendChild(btn);
    } else {
      pager.classList.add("hidden");
    }
  } catch (e) {
    tbody.innerHTML = "";
    empty.textContent = e.message || String(e);
    empty.classList.remove("hidden");
    pager.classList.add("hidden");
  }
}

function formatCell(v) {
  if (v === null || v === undefined) return "—";
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
    alert(e.message);
  }
}

function buildFormFields(schemaKey, entity, isCreate) {
  const schema = CRUD_SCHEMA[schemaKey];
  const frag = document.createDocumentFragment();
  for (const f of schema.fields) {
    if (f.editOnly && isCreate) continue;
    const wrap = document.createElement("label");
    wrap.textContent = f.label;
    let input;
    if (f.type === "textarea") {
      input = document.createElement("textarea");
      input.rows = 4;
    } else if (f.type === "checkbox") {
      input = document.createElement("input");
      input.type = "checkbox";
      input.name = f.key;
      input.checked = entity ? Boolean(entity[f.key]) : false;
      wrap.insertBefore(input, wrap.firstChild);
      frag.appendChild(wrap);
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
    } else if (f.type === "number") {
      input = document.createElement("input");
      input.type = "number";
      input.name = f.key;
      if (entity && entity[f.key] !== undefined) input.value = entity[f.key];
    } else if (f.type === "json") {
      input = document.createElement("textarea");
      input.rows = 5;
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
    if (f.required) input.required = true;
    wrap.appendChild(input);
    frag.appendChild(wrap);
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
    if (f.type === "json") {
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
    if (f.key === "parent_id" && val.trim() === "") {
      payload[f.key] = null;
      continue;
    }
    payload[f.key] = val;
  }
  if (schemaKey === "categories" && payload.parent_id === "") payload.parent_id = null;
  return payload;
}

async function openModal(schemaKey, id) {
  const sec = SECTIONS.find((s) => s.id === schemaKey);
  const isCreate = !id;
  const modal = document.getElementById("modal");
  const form = document.getElementById("modal-form");
  const title = document.getElementById("modal-title");
  form.innerHTML = "";
  title.textContent = isCreate ? "Создание" : "Редактирование";

  let entity = null;
  if (!isCreate) {
    try {
      entity = await apiFetch(`${sec.resource}/${id}`);
    } catch (e) {
      alert(e.message);
      return;
    }
  }

  form.appendChild(buildFormFields(schemaKey, entity, isCreate));
  modal.classList.remove("hidden");
  modal.setAttribute("aria-hidden", "false");

  form.onsubmit = async (ev) => {
    ev.preventDefault();
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
      alert(e.message);
    }
  };
}

function closeModal() {
  const modal = document.getElementById("modal");
  modal.classList.add("hidden");
  modal.setAttribute("aria-hidden", "true");
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
        <dt>Телефон</dt><dd>${escapeHtml(o.customer_phone)}</dd>
        <dt>Email</dt><dd>${escapeHtml(o.customer_email)}</dd>
        <dt>Сумма</dt><dd>${escapeHtml(o.total_amount ?? "—")}</dd>
        <dt>Создан</dt><dd>${escapeHtml(o.created_at)}</dd>
        <dt>Обновлён</dt><dd>${escapeHtml(o.updated_at)}</dd>
      </dl>
      <h3>Комментарий</h3>
      <p>${escapeHtml(o.comment || "—")}</p>
      <h3>Позиции</h3>
      <pre>${escapeHtml(JSON.stringify(o.items, null, 2))}</pre>
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
          skipAuth: true,
        });
        alert("Статус обновлён");
        showOrderDetail(id, o.order_number);
      } catch (e) {
        alert(e.message);
      }
    });
  } catch (e) {
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
    id: "svc-slug",
    name: "GET Услуга по slug",
    method: "GET",
    path: "/services/by-slug/{slug}",
    params: [{ key: "slug", label: "Slug" }],
  },
  {
    id: "search",
    name: "GET Поиск",
    method: "GET",
    path: "/search",
    query: [
      { key: "q", label: "Запрос (от 2 симв.)", default: "мерник" },
      { key: "type", label: "Тип: all|products|services|categories", default: "all" },
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
      { key: "type", label: "Тип (category|product|service)", default: "category" },
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
    pre.textContent = `Ошибка ${e.status || ""}: ${e.message}`;
  }
}

/* ——— Login & boot ——— */

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
    goSection("products");
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

document.getElementById("btn-refresh").addEventListener("click", () => loadCrudTable());
document.getElementById("btn-new").addEventListener("click", () => {
  const sec = SECTIONS.find((s) => s.id === state.section);
  if (sec && sec.type === "crud") openModal(sec.id, null);
});

document.querySelectorAll(".modal [data-close]").forEach((el) => {
  el.addEventListener("click", closeModal);
});

buildNav();
initApiPlayground();
updateAuthUi();

if (state.token) {
  document.getElementById("view-login").classList.add("hidden");
  goSection("products");
} else {
  goSection("login");
}
