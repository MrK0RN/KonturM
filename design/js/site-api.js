(function () {
  "use strict";

  function apiBase() {
    var bp = typeof window.__KONTURM_BASE_PATH__ === "string" ? window.__KONTURM_BASE_PATH__ : "";
    return (bp || "") + "/api";
  }

  function konturmFetchJson(path, options) {
    var url = apiBase() + path;
    var opts = options || {};
    opts.headers = opts.headers || {};
    if (!opts.headers["Accept"]) opts.headers["Accept"] = "application/json";
    if (opts.body && typeof opts.body === "string" && !opts.headers["Content-Type"]) {
      opts.headers["Content-Type"] = "application/json";
    }
    return fetch(url, opts).then(function (res) {
      if (!res.ok) {
        return res.text().then(function (t) {
          throw new Error(res.status + " " + (t || res.statusText));
        });
      }
      return res.json();
    });
  }

  function normalizeTree(data) {
    if (Array.isArray(data)) return data;
    if (!data || typeof data !== "object") return [];
    if (Array.isArray(data["hydra:member"])) return data["hydra:member"];
    if (Array.isArray(data.items)) return data.items;
    if (Array.isArray(data.data)) return data.data;
    if (data.id && data.name) return [data];
    return [];
  }

  function normalizeCategoryProductsPayload(data) {
    if (!data || typeof data !== "object") {
      return { items: [], pagination: null, filters: null };
    }
    if (Array.isArray(data.items)) {
      return {
        items: data.items,
        pagination: data.pagination || null,
        filters: data.filters || null,
      };
    }
    var member = Array.isArray(data["hydra:member"]) ? data["hydra:member"] : null;
    if (member) {
      var nestedItems = Array.isArray(member[0]) ? member[0] : [];
      var pagination = member[1] && typeof member[1] === "object" && !Array.isArray(member[1]) ? member[1] : null;
      var filters = member[2] && typeof member[2] === "object" && !Array.isArray(member[2]) ? member[2] : null;
      if (nestedItems.length || pagination || filters) {
        return { items: nestedItems, pagination: pagination, filters: filters };
      }
      return { items: member, pagination: null, filters: null };
    }
    return { items: [], pagination: null, filters: null };
  }

  function normalizeCartPayload(data) {
    if (!data || typeof data !== "object") {
      return { items: [], total_quantity: 0, total_amount: 0 };
    }
    if (Array.isArray(data.items)) {
      return {
        items: data.items,
        total_quantity: data.total_quantity | 0,
        total_amount: Number(data.total_amount) || 0,
      };
    }
    var m = data["hydra:member"];
    if (Array.isArray(m) && m.length >= 3) {
      return {
        items: Array.isArray(m[0]) ? m[0] : [],
        total_quantity: Number(m[1]) | 0,
        total_amount: Number(m[2]) || 0,
      };
    }
    return { items: [], total_quantity: 0, total_amount: 0 };
  }

  function fmtPriceRu(v) {
    var n = Number(v);
    if (!Number.isFinite(n)) return String(v || "");
    return (
      n.toLocaleString("ru-RU", { maximumFractionDigits: 0, minimumFractionDigits: 0 }) + " р."
    );
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function mediaUrl(photo) {
    if (!photo) return "";
    if (/^https?:\/\//i.test(photo)) return photo;
    var bp = typeof window.__KONTURM_BASE_PATH__ === "string" ? window.__KONTURM_BASE_PATH__ : "";
    return (bp || "") + (photo.charAt(0) === "/" ? "" : "/") + photo;
  }

  function updateCartLinkLabel(qty) {
    var link = document.querySelector(".site-header__cart");
    if (!link) return;
    var n = qty | 0;
    link.setAttribute("aria-label", n > 0 ? "Корзина, позиций: " + n : "Корзина");
  }

  function refreshCartBadge() {
    return konturmFetchJson("/cart")
      .then(normalizeCartPayload)
      .then(function (cart) {
        updateCartLinkLabel(cart.total_quantity);
        return cart;
      })
      .catch(function () {
        updateCartLinkLabel(0);
        return { items: [], total_quantity: 0, total_amount: 0 };
      });
  }

  function addProductToCart(productId, quantity) {
    return konturmFetchJson("/cart/items", {
      method: "POST",
      body: JSON.stringify({
        type: "product",
        id: String(productId),
        quantity: quantity || 1,
      }),
    })
      .then(normalizeCartPayload)
      .then(function (cart) {
        updateCartLinkLabel(cart.total_quantity);
        return cart;
      });
  }

  function patchCartItem(itemId, quantity) {
    return konturmFetchJson("/cart/items/" + encodeURIComponent(itemId), {
      method: "PATCH",
      body: JSON.stringify({ quantity: quantity }),
    }).then(normalizeCartPayload);
  }

  function deleteCartItem(itemId) {
    return konturmFetchJson("/cart/items/" + encodeURIComponent(itemId), {
      method: "DELETE",
    }).then(normalizeCartPayload);
  }

  function clearCart() {
    return konturmFetchJson("/cart", { method: "DELETE" }).then(normalizeCartPayload);
  }

  var acTimer;
  function setupHeaderSearchAutocomplete() {
    var input = document.getElementById("header-search-q");
    var datalist = document.getElementById("site-header-search-datalist");
    if (!input || !datalist) return;

    function run() {
      var q = (input.value || "").trim();
      if (q.length < 2) return;
      konturmFetchJson("/search/autocomplete?q=" + encodeURIComponent(q) + "&limit=12")
        .then(function (data) {
          datalist.innerHTML = "";
          var seen = {};
          function add(label) {
            if (!label || seen[label]) return;
            seen[label] = 1;
            var opt = document.createElement("option");
            opt.value = label;
            datalist.appendChild(opt);
          }
          ["products", "services", "categories"].forEach(function (k) {
            var arr = data[k] || [];
            arr.forEach(function (row) {
              add(row.name || row.title || "");
            });
          });
        })
        .catch(function () {});
    }

    input.addEventListener("input", function () {
      clearTimeout(acTimer);
      acTimer = setTimeout(run, 220);
    });
  }

  window.KonturM = {
    apiBase: apiBase,
    fetchJson: konturmFetchJson,
    normalizeTree: normalizeTree,
    normalizeCategoryProductsPayload: normalizeCategoryProductsPayload,
    normalizeCartPayload: normalizeCartPayload,
    fmtPriceRu: fmtPriceRu,
    escapeHtml: escapeHtml,
    mediaUrl: mediaUrl,
    refreshCartBadge: refreshCartBadge,
    addProductToCart: addProductToCart,
    patchCartItem: patchCartItem,
    deleteCartItem: deleteCartItem,
    clearCart: clearCart,
    setupHeaderSearchAutocomplete: setupHeaderSearchAutocomplete,
    updateCartLinkLabel: updateCartLinkLabel,
    setupMobileNav: setupMobileNav,
  };

  function setupMobileNav() {
    var burger = document.querySelector(".site-header__burger");
    var drawer = document.getElementById("site-mobile-drawer");
    var closeBtn = document.querySelector(".site-header__drawer-close");
    if (!burger || !drawer) return;

    function openNav() {
      drawer.classList.add("is-open");
      document.body.classList.add("is-nav-open");
      burger.setAttribute("aria-expanded", "true");
      if (closeBtn) closeBtn.focus();
    }

    function closeNav() {
      drawer.classList.remove("is-open");
      document.body.classList.remove("is-nav-open");
      burger.setAttribute("aria-expanded", "false");
      burger.focus();
    }

    burger.addEventListener("click", openNav);
    if (closeBtn) closeBtn.addEventListener("click", closeNav);

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && drawer.classList.contains("is-open")) closeNav();
    });
  }

  function scheduleCartBadgeRefresh() {
    function run() {
      refreshCartBadge();
    }
    if (typeof window.requestIdleCallback === "function") {
      window.requestIdleCallback(run, { timeout: 2500 });
    } else {
      window.setTimeout(run, 0);
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    scheduleCartBadgeRefresh();
    setupHeaderSearchAutocomplete();
    setupMobileNav();
  });
})();
