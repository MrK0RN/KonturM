# Промпт для создания REST API (PHP, PostgreSQL, API Platform) — merniki.ru

## Контекст

Необходимо разработать REST API для сайта **merniki.ru** — корпоративного сайта производителя средств измерений ООО «Контур-М». API должен обеспечивать функционал промышленного каталога, инструмента для инженеров и продающего сайта.

**О компании:**
- Работает на рынке с 1999 года
- Имеет собственное производство в Казани
- Аккредитация на поверку средств измерений

**Целевая аудитория:**
- Инженеры по оборудованию на нефтебазах и АЗС
- Менеджеры по закупкам
- Руководители компаний
- Организации, закупающие продукцию для перепродажи

**Технологический стек:**
- Язык: PHP 8.1+
- Фреймворк: Symfony 6.4+
- API платформа: API Platform 3.2+
- ORM: Doctrine 2
- База данных: PostgreSQL 15+
- Кэширование: Symfony Cache (файловое)
- Хостинг: Spaceweb

---

## Сущности базы данных

### 1. Категория (Category)

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | UUID | PRIMARY KEY | Уникальный идентификатор |
| `parent_id` | UUID | FOREIGN KEY → categories(id), NULL | Родительская категория |
| `name` | VARCHAR(255) | NOT NULL | Название категории (Мерники, Метроштоки, Рулетки, Пробоотборники) |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | URL-идентификатор |
| `description` | TEXT | NULL | Описание категории для SEO |
| `photo` | VARCHAR(500) | NULL | URL изображения категории |
| `photo_alt` | VARCHAR(255) | NULL | Альтернативный текст |
| `is_favorite_main` | BOOLEAN | DEFAULT false | Показывать на главной странице |
| `is_favorite_sidebar` | BOOLEAN | DEFAULT false | Показывать в боковом меню |
| `sort_order` | INTEGER | DEFAULT 0 | Порядок сортировки |
| `display_mode` | VARCHAR(50) | DEFAULT 'subcategories_only' | Режим отображения: 'subcategories_only', 'mixed' |
| `aggregate_products` | BOOLEAN | DEFAULT false | Агрегировать товары из подкатегорий |
| `meta_title` | VARCHAR(255) | NULL | SEO заголовок |
| `meta_description` | TEXT | NULL | SEO описание |
| `created_at` | TIMESTAMP | DEFAULT NOW() | Дата создания |
| `updated_at` | TIMESTAMP | DEFAULT NOW() | Дата обновления |

**Индексы:**
- `parent_id` — для быстрого получения дочерних категорий
- `slug` — уникальный индекс для поиска по URL
- `is_favorite_main`, `is_favorite_sidebar` — для выборок избранных
- `sort_order` — для сортировки

---

### 2. Товар (Product)

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | UUID | PRIMARY KEY | Уникальный идентификатор |
| `category_id` | UUID | FOREIGN KEY → categories(id), NOT NULL | Категория товара |
| `name` | VARCHAR(255) | NOT NULL | Название товара |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | URL-идентификатор |
| `article` | VARCHAR(100) | UNIQUE, NULL | Артикул товара |
| `photo` | VARCHAR(500) | NULL | Основное изображение |
| `photo_alt` | VARCHAR(255) | NULL | Альтернативный текст |
| `description` | TEXT | NULL | Описание товара |
| `technical_specs` | JSONB | NULL | Технические характеристики (объем, материал, диаметр, высота, тип присоединения, рабочая среда, давление) |
| `price` | DECIMAL(10,2) | NULL | Цена |
| `stock_status` | VARCHAR(50) | DEFAULT 'on_order' | Статус наличия: 'in_stock', 'on_order', 'under_manufacturing' |
| `manufacturing_time` | VARCHAR(100) | NULL | Срок изготовления (например, "5-7 дней", "под заказ") |
| `gost_number` | VARCHAR(100) | NULL | Номер ГОСТ |
| `has_verification` | BOOLEAN | DEFAULT false | Наличие поверки |
| `drawings` | JSONB | NULL | Массив URL чертежей в PDF |
| `documents` | JSONB | NULL | Массив URL документов (паспорта) |
| `certificates` | JSONB | NULL | Массив URL сертификатов |
| `meta_title` | VARCHAR(255) | NULL | SEO заголовок |
| `meta_description` | TEXT | NULL | SEO описание |
| `created_at` | TIMESTAMP | DEFAULT NOW() | Дата создания |
| `updated_at` | TIMESTAMP | DEFAULT NOW() | Дата обновления |

**Индексы:**
- `category_id` — для выборки товаров категории
- `slug` — уникальный индекс для поиска по URL
- `article` — уникальный индекс для поиска по артикулу
- GIN индекс на `technical_specs` — для эффективной фильтрации по JSONB
- GIN индекс для полнотекстового поиска (`name`, `description`, `article`, `gost_number`)
- `price` — для ценовой фильтрации
- `has_verification` — для фильтрации по наличию поверки
- `stock_status` — для фильтрации по наличию
- `created_at` — для сортировки новинок

---

### 3. Услуга (Service)

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | UUID | PRIMARY KEY | Уникальный идентификатор |
| `name` | VARCHAR(255) | NOT NULL | Название услуги (поверка, калибровка, изготовление по чертежам) |
| `slug` | VARCHAR(255) | UNIQUE, NOT NULL | URL-идентификатор |
| `description` | TEXT | NULL | Описание услуги |
| `price` | DECIMAL(10,2) | NULL | Базовая цена (может быть договорной) |
| `price_type` | VARCHAR(50) | DEFAULT 'fixed' | Тип цены: 'fixed', 'negotiable', 'from' |
| `photo` | VARCHAR(500) | NULL | Изображение для услуги |
| `requires_technical_spec` | BOOLEAN | DEFAULT false | Требуется прикрепить техзадание |
| `meta_title` | VARCHAR(255) | NULL | SEO заголовок |
| `meta_description` | TEXT | NULL | SEO описание |
| `sort_order` | INTEGER | DEFAULT 0 | Порядок сортировки |
| `created_at` | TIMESTAMP | DEFAULT NOW() | Дата создания |
| `updated_at` | TIMESTAMP | DEFAULT NOW() | Дата обновления |

**Индексы:**
- `slug` — уникальный индекс
- `sort_order` — для сортировки

---

### 4. Заказ (Order)

| Поле | Тип | Ограничения | Описание |
|------|-----|-------------|----------|
| `id` | UUID | PRIMARY KEY | Уникальный идентификатор |
| `order_number` | VARCHAR(50) | UNIQUE, NOT NULL | Номер заказа (генерируется) |
| `customer_name` | VARCHAR(255) | NOT NULL | Имя клиента |
| `customer_company` | VARCHAR(255) | NULL | Компания |
| `customer_phone` | VARCHAR(50) | NOT NULL | Телефон |
| `customer_email` | VARCHAR(255) | NOT NULL | Email |
| `items` | JSONB | NOT NULL | Массив позиций заказа (тип, id, название, артикул, количество, цена) |
| `attachments` | JSONB | NULL | Массив путей к прикрепленным файлам (техзадания, чертежи) |
| `comment` | TEXT | NULL | Комментарий к заказу |
| `total_amount` | DECIMAL(12,2) | NULL | Общая сумма заказа |
| `status` | VARCHAR(50) | DEFAULT 'new' | Статус: 'new', 'processing', 'completed', 'cancelled' |
| `created_at` | TIMESTAMP | DEFAULT NOW() | Дата создания |
| `updated_at` | TIMESTAMP | DEFAULT NOW() | Дата обновления |

**Индексы:**
- `order_number` — уникальный индекс для поиска заказа
- `status` — для фильтрации по статусу
- `created_at` — для сортировки
- `customer_email` — для поиска заказов клиента

---

## Эндпоинты API

### Базовый URL: `/api`

---

## 1. Категории

### 1.1. Получить дерево категорий
```
GET /api/categories/tree
```

**Описание:** Возвращает полное дерево категорий с вложенностью. Используется для построения навигации по каталогу.

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `include_products` | boolean | Включить товары в каждый узел (по умолчанию false) |
| `max_depth` | integer | Максимальная глубина вложенности |

---

### 1.2. Получить избранные категории (главная страница)
```
GET /api/categories/favorites/main
```

**Описание:** Возвращает категории для отображения на главной странице (флаг `is_favorite_main = true`), отсортированные по `sort_order`.

---

### 1.3. Получить избранные категории (боковое меню)
```
GET /api/categories/favorites/sidebar
```

**Описание:** Возвращает категории для отображения в боковом меню (флаг `is_favorite_sidebar = true`), отсортированные по `sort_order`.

---

### 1.4. Получить категорию по slug
```
GET /api/categories/by-slug/{slug}
```

**Описание:** Возвращает детальную информацию о категории с хлебными крошками, SEO-данными и каноническим URL.

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `include_children` | boolean | Включить дочерние категории |
| `include_products` | boolean | Включить товары текущего уровня (ограниченно) |

**Дополнительные поля в ответе:**
- `has_children` — наличие дочерних категорий
- `has_products` — наличие товаров в текущей категории
- `breadcrumbs` — навигационная цепочка
- `canonical_url` — канонический URL

---

### 1.5. Получить товары категории (с фильтрацией)
```
GET /api/categories/{slug}/products
```

**Описание:** Возвращает товары из указанной категории с возможностью фильтрации по техническим характеристикам.

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `page` | integer | Номер страницы (по умолчанию 1) |
| `limit` | integer | Количество на странице (по умолчанию 20, макс 100) |
| `sort` | string | Поле сортировки: `name`, `price`, `created_at` (по умолчанию `created_at`) |
| `order` | string | Направление: `asc` или `desc` (по умолчанию `desc`) |
| `aggregate` | boolean | Агрегировать товары из подкатегорий (по умолчанию false) |
| `filters` | object | Фильтры по характеристикам (объем, материал, диаметр, высота, тип присоединения, рабочая среда, давление, наличие поверки) |
| `min_price` | decimal | Минимальная цена |
| `max_price` | decimal | Максимальная цена |
| `search` | string | Поиск по названию товара |

**Формат фильтров:**
```json
{
  "filters": {
    "volume": ["1л", "2л", "5л"],
    "material": ["нержавеющая сталь", "алюминий"],
    "diameter": ["50мм", "100мм"],
    "has_verification": true
  }
}
```

**Ответ включает:**
- Массив товаров с основными полями
- Мета-информация о пагинации
- Доступные фильтры для текущей категории

---

### 1.6. Получить доступные фильтры для категории
```
GET /api/categories/{slug}/filters
```

**Описание:** Возвращает все возможные значения характеристик для товаров категории (с учётом агрегации).

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `aggregate` | boolean | Учитывать товары из подкатегорий (по умолчанию true) |

---

### 1.7. CRUD операции (API Platform)
```
GET    /api/categories          - Список категорий
GET    /api/categories/{id}     - Категория по ID
POST   /api/categories          - Создать категорию
PUT    /api/categories/{id}     - Обновить категорию
DELETE /api/categories/{id}     - Удалить категорию
```

---

## 2. Товары

### 2.1. Получить товар по slug
```
GET /api/products/by-slug/{slug}
```

**Описание:** Возвращает детальную информацию о товаре с полной технической документацией.

**Ответ включает:**
- Все поля товара
- Технические характеристики (структурированно из `technical_specs`)
- Информацию о категории (id, name, slug)
- Хлебные крошки (breadcrumbs)
- Канонический URL
- SEO-мета-теги
- Массивы URL для чертежей (`drawings`), документов (`documents`), сертификатов (`certificates`)
- Статус наличия (`stock_status`) и срок изготовления (`manufacturing_time`)

---

### 2.2. Получить товары по артикулам
```
GET /api/products/by-articles
```

**Описание:** Возвращает товары по списку артикулов (для быстрого добавления в заявку или проверки наличия).

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `articles` | string | Список артикулов через запятую (обязательный) |

---

### 2.3. Получить популярные товары
```
GET /api/products/popular
```

**Описание:** Возвращает популярные товары для отображения на главной странице.

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `limit` | integer | Количество товаров (по умолчанию 8, макс 20) |

---

### 2.4. Получить новинки
```
GET /api/products/new
```

**Описание:** Возвращает новые товары, отсортированные по дате создания (сначала новые).

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `limit` | integer | Количество товаров (по умолчанию 8, макс 20) |

---

### 2.5. CRUD операции (API Platform)
```
GET    /api/products          - Список товаров
GET    /api/products/{id}     - Товар по ID
POST   /api/products          - Создать товар
PUT    /api/products/{id}     - Обновить товар
DELETE /api/products/{id}     - Удалить товар
```

---

## 3. Услуги

### 3.1. Получить список услуг
```
GET /api/services
```

**Описание:** Возвращает список услуг (поверка, калибровка, изготовление по чертежам).

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `page` | integer | Номер страницы (по умолчанию 1) |
| `limit` | integer | Количество на странице (по умолчанию 20) |

---

### 3.2. Получить услугу по slug
```
GET /api/services/by-slug/{slug}
```

**Описание:** Возвращает детальную информацию об услуге.

**Ответ включает:**
- Название и описание
- Цену и тип цены (`price_type`: fixed, negotiable, from)
- Информацию о необходимости прикрепления техзадания (`requires_technical_spec`)
- SEO-мета-теги

---

### 3.3. CRUD операции (API Platform)
```
GET    /api/services/{id}     - Услуга по ID
POST   /api/services          - Создать услугу
PUT    /api/services/{id}     - Обновить услугу
DELETE /api/services/{id}     - Удалить услугу
```

---

## 4. Заказы и заявки

### 4.1. Создать заказ (отправить заявку)
```
POST /api/orders
```

**Описание:** Создание новой заявки. Процесс максимально прост, но собирает всю необходимую информацию.

**Тело запроса:**
```json
{
  "customer_name": "Иванов Иван",
  "customer_company": "ООО Нефтегаз",
  "customer_phone": "+7 (999) 123-45-67",
  "customer_email": "ivanov@example.com",
  "items": [
    {
      "type": "product",
      "id": "uuid",
      "name": "Мерник М-1-1000",
      "article": "М-1-1000",
      "quantity": 2,
      "price": 12500
    },
    {
      "type": "service",
      "id": "uuid",
      "name": "Поверка мерника",
      "quantity": 2,
      "price": 3000
    }
  ],
  "comment": "Нужна поверка с документами",
  "attachments": ["/uploads/technical_spec.pdf"]
}
```

**Валидация:**
- Обязательные поля: `customer_name`, `customer_phone`, `customer_email`
- Хотя бы одна позиция в `items`
- Email должен быть корректным
- Телефон в международном или российском формате
- Количество — целое положительное число

**После отправки:**
- Генерация номера заказа (формат: `M-YYYY-XXXXX`)
- Сохранение в БД
- Отправка уведомлений менеджерам (email, Telegram, админка)
- Возврат номера заказа и статуса

---

### 4.2. Получить статус заказа
```
GET /api/orders/{order_number}/status
```

**Описание:** Проверка статуса заказа по номеру.

**Ответ:**
```json
{
  "order_number": "M-2024-00123",
  "status": "processing",
  "status_text": "Ваш заказ обрабатывается",
  "created_at": "2024-01-15T10:30:00Z"
}
```

---

### 4.3. CRUD операции (админка)
```
GET    /api/orders                - Список заказов (с фильтрацией по статусу, дате)
GET    /api/orders/{id}           - Заказ по ID
PATCH  /api/orders/{id}/status    - Обновить статус заказа
PUT    /api/orders/{id}           - Обновить заказ
DELETE /api/orders/{id}           - Удалить заказ
```

---

## 5. Поиск

### 5.1. Полнотекстовый поиск
```
GET /api/search
```

**Описание:** Полнотекстовый поиск по товарам, услугам и категориям с использованием PostgreSQL FULLTEXT (pg_trgm).

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `q` | string | **Обязательный.** Поисковый запрос (минимум 2 символа) |
| `type` | string | Тип результатов: `all`, `products`, `services`, `categories` (по умолчанию `all`) |
| `page` | integer | Номер страницы (по умолчанию 1) |
| `limit` | integer | Количество на странице (по умолчанию 20) |
| `filters` | object | Фильтры по характеристикам (только для товаров) |
| `min_price` | decimal | Минимальная цена (только для товаров) |
| `max_price` | decimal | Максимальная цена (только для товаров) |

**Поисковые поля:**
- Товары: `name`, `description`, `technical_specs`, `article`, `gost_number`
- Услуги: `name`, `description`
- Категории: `name`

**Релевантность:** Сортировка по степени совпадения с использованием `similarity()`.

---

### 5.2. Автодополнение (подсказки)
```
GET /api/search/autocomplete
```

**Описание:** Быстрые подсказки при вводе запроса. Возвращает ограниченное количество результатов.

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `q` | string | **Обязательный.** Часть поискового запроса (минимум 2 символа) |
| `limit` | integer | Максимальное количество результатов (по умолчанию 10) |

**Ответ:**
```json
{
  "data": {
    "products": [
      {
        "name": "Мерник М-1-1000",
        "slug": "mernik-m-1-1000",
        "type": "product",
        "photo": "https://...",
        "article": "М-1-1000",
        "price": 12500
      }
    ],
    "services": [
      {
        "name": "Поверка мерников",
        "slug": "poverka-mernikov",
        "type": "service",
        "price": "от 3000",
        "price_type": "from"
      }
    ],
    "categories": [
      {
        "name": "Мерники",
        "slug": "merniki",
        "type": "category"
      }
    ]
  }
}
```

---

## 6. Корзина

### 6.1. Получить текущую корзину
```
GET /api/cart
```

**Описание:** Получение текущей корзины. Используется session или JWT для идентификации неавторизованного пользователя.

**Ответ:**
```json
{
  "items": [
    {
      "id": "cart_item_uuid",
      "type": "product",
      "product_id": "uuid",
      "name": "Мерник М-1-1000",
      "article": "М-1-1000",
      "slug": "mernik-m-1-1000",
      "photo": "https://...",
      "quantity": 2,
      "price": 12500,
      "total": 25000
    }
  ],
  "total_quantity": 2,
  "total_amount": 25000
}
```

---

### 6.2. Добавить товар или услугу в корзину
```
POST /api/cart/items
```

**Тело запроса:**
```json
{
  "type": "product",
  "id": "uuid",
  "quantity": 1
}
```

**Поддерживаемые типы:** `product`, `service`

---

### 6.3. Обновить количество позиции
```
PATCH /api/cart/items/{item_id}
```

**Тело запроса:**
```json
{
  "quantity": 3
}
```

---

### 6.4. Удалить позицию из корзины
```
DELETE /api/cart/items/{item_id}
```

---

### 6.5. Очистить корзину
```
DELETE /api/cart
```

---

### 6.6. Оформить заказ из корзины
```
POST /api/cart/checkout
```

**Описание:** Преобразует текущую корзину в заказ. Аналогично `POST /api/orders`, но данные о товарах берутся из корзины.

**Тело запроса:**
```json
{
  "customer_name": "Иванов Иван",
  "customer_company": "ООО Нефтегаз",
  "customer_phone": "+7 (999) 123-45-67",
  "customer_email": "ivanov@example.com",
  "comment": "Доставка до 15:00",
  "attachments": ["/uploads/technical_spec.pdf"]
}
```

**После успешного оформления:** корзина очищается.

---

## 7. SEO и мета-информация

### 7.1. Получить мета-теги для страницы
```
GET /api/seo/{type}/{slug}
```

**Описание:** Возвращает SEO-метаданные для любой страницы (категории, товара, услуги).

**Параметры пути:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `type` | string | `category`, `product`, `service` |
| `slug` | string | Slug сущности |

**Логика генерации:**
- Если ручные `meta_title` и `meta_description` заданы — использовать их
- Иначе генерировать автоматически:
  - **title:** `{Название} — купить в Контур-М`
  - **description:** Из описания (первые 150-160 символов) или из характеристик

**Ответ:**
```json
{
  "data": {
    "title": "Мерник М-1-1000 — купить в Контур-М",
    "description": "Мерник М-1-1000 объемом 1 литр из нержавеющей стали. Соответствует ГОСТ. В наличии поверка. Цена 12500 руб.",
    "canonical_url": "https://merniki.ru/products/mernik-m-1-1000",
    "robots": "index, follow"
  }
}
```

---

### 7.2. Сгенерировать canonical URL
```
GET /api/seo/canonical
```

**Описание:** Возвращает канонический URL для текущего запроса с учётом параметров фильтрации.

**Query параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| `url` | string | Текущий URL с параметрами (опционально) |
| `type` | string | `category`, `product`, `service` |

**Принцип:**
- Для категорий: удалить все параметры фильтрации, оставить базовый URL
- Для товаров и услуг: вернуть базовый URL

---

## Сводная таблица всех эндпоинтов

| № | Метод | Эндпоинт | Описание |
|---|-------|----------|----------|
| **Категории (публичные)** |
| 1 | GET | `/api/categories/tree` | Получить дерево категорий |
| 2 | GET | `/api/categories/favorites/main` | Избранные категории (главная) |
| 3 | GET | `/api/categories/favorites/sidebar` | Избранные категории (боковое меню) |
| 4 | GET | `/api/categories/by-slug/{slug}` | Получить категорию по slug |
| 5 | GET | `/api/categories/{slug}/products` | Товары категории с фильтрацией |
| 6 | GET | `/api/categories/{slug}/filters` | Доступные фильтры категории |
| **Категории (CRUD)** |
| 7 | GET | `/api/categories` | Список категорий |
| 8 | GET | `/api/categories/{id}` | Категория по ID |
| 9 | POST | `/api/categories` | Создать категорию |
| 10 | PUT | `/api/categories/{id}` | Обновить категорию |
| 11 | DELETE | `/api/categories/{id}` | Удалить категорию |
| **Товары (публичные)** |
| 12 | GET | `/api/products/by-slug/{slug}` | Получить товар по slug |
| 13 | GET | `/api/products/by-articles` | Получить товары по артикулам |
| 14 | GET | `/api/products/popular` | Популярные товары |
| 15 | GET | `/api/products/new` | Новинки |
| **Товары (CRUD)** |
| 16 | GET | `/api/products` | Список товаров |
| 17 | GET | `/api/products/{id}` | Товар по ID |
| 18 | POST | `/api/products` | Создать товар |
| 19 | PUT | `/api/products/{id}` | Обновить товар |
| 20 | DELETE | `/api/products/{id}` | Удалить товар |
| **Услуги (публичные)** |
| 21 | GET | `/api/services` | Список услуг |
| 22 | GET | `/api/services/by-slug/{slug}` | Услуга по slug |
| **Услуги (CRUD)** |
| 23 | GET | `/api/services/{id}` | Услуга по ID |
| 24 | POST | `/api/services` | Создать услугу |
| 25 | PUT | `/api/services/{id}` | Обновить услугу |
| 26 | DELETE | `/api/services/{id}` | Удалить услугу |
| **Заказы (публичные)** |
| 27 | POST | `/api/orders` | Создать заказ |
| 28 | GET | `/api/orders/{order_number}/status` | Статус заказа |
| **Заказы (админка)** |
| 29 | GET | `/api/orders` | Список заказов |
| 30 | GET | `/api/orders/{id}` | Заказ по ID |
| 31 | PATCH | `/api/orders/{id}/status` | Обновить статус заказа |
| 32 | PUT | `/api/orders/{id}` | Обновить заказ |
| 33 | DELETE | `/api/orders/{id}` | Удалить заказ |
| **Поиск** |
| 34 | GET | `/api/search` | Полнотекстовый поиск |
| 35 | GET | `/api/search/autocomplete` | Автодополнение |
| **Корзина** |
| 36 | GET | `/api/cart` | Получить корзину |
| 37 | POST | `/api/cart/items` | Добавить позицию в корзину |
| 38 | PATCH | `/api/cart/items/{item_id}` | Обновить количество |
| 39 | DELETE | `/api/cart/items/{item_id}` | Удалить позицию |
| 40 | DELETE | `/api/cart` | Очистить корзину |
| 41 | POST | `/api/cart/checkout` | Оформить заказ из корзины |
| **SEO** |
| 42 | GET | `/api/seo/{type}/{slug}` | Мета-теги страницы |
| 43 | GET | `/api/seo/canonical` | Канонический URL |
| **ИТОГО** | | | **43 эндпоинта** |

---

## PostgreSQL индексы

```sql
-- Расширение для полнотекстового поиска
CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- GIN индексы для JSONB
CREATE INDEX idx_products_technical_specs ON products USING GIN (technical_specs);
CREATE INDEX idx_products_drawings ON products USING GIN (drawings);
CREATE INDEX idx_products_documents ON products USING GIN (documents);
CREATE INDEX idx_products_certificates ON products USING GIN (certificates);
CREATE INDEX idx_orders_items ON orders USING GIN (items);
CREATE INDEX idx_orders_attachments ON orders USING GIN (attachments);

-- GIN индексы для полнотекстового поиска
CREATE INDEX idx_products_name_trgm ON products USING GIN (name gin_trgm_ops);
CREATE INDEX idx_products_description_trgm ON products USING GIN (description gin_trgm_ops);
CREATE INDEX idx_products_article_trgm ON products USING GIN (article gin_trgm_ops);
CREATE INDEX idx_products_gost_number_trgm ON products USING GIN (gost_number gin_trgm_ops);
CREATE INDEX idx_services_name_trgm ON services USING GIN (name gin_trgm_ops);
CREATE INDEX idx_categories_name_trgm ON categories USING GIN (name gin_trgm_ops);

-- Индексы для фильтрации
CREATE INDEX idx_products_category_id ON products (category_id);
CREATE INDEX idx_products_price ON products (price);
CREATE INDEX idx_products_has_verification ON products (has_verification);
CREATE INDEX idx_products_stock_status ON products (stock_status);
CREATE INDEX idx_products_created_at ON products (created_at);

-- Индексы для категорий
CREATE INDEX idx_categories_parent_id ON categories (parent_id);
CREATE INDEX idx_categories_slug ON categories (slug);
CREATE INDEX idx_categories_is_favorite_main ON categories (is_favorite_main);
CREATE INDEX idx_categories_is_favorite_sidebar ON categories (is_favorite_sidebar);
CREATE INDEX idx_categories_sort_order ON categories (sort_order);

-- Индексы для заказов
CREATE INDEX idx_orders_order_number ON orders (order_number);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_created_at ON orders (created_at);
CREATE INDEX idx_orders_customer_email ON orders (customer_email);

-- Индексы для услуг
CREATE INDEX idx_services_slug ON services (slug);
CREATE INDEX idx_services_sort_order ON services (sort_order);
```

---

## Кэширование (файловое)

| Объект | TTL | Ключ кэша |
|--------|-----|-----------|
| Дерево категорий | 1 час | `category_tree` |
| Избранные категории (главная) | 1 час | `favorites_main` |
| Избранные категории (боковое меню) | 1 час | `favorites_sidebar` |
| Доступные фильтры | 10 минут | `filters_{category_id}_{aggregate}` |
| Товар по slug | 24 часа | `product_{slug}` |
| Категория по slug | 24 часа | `category_{slug}` |
| Популярные товары | 6 часов | `products_popular` |
| Новинки | 6 часов | `products_new` |
| Список услуг | 6 часов | `services_list` |
| SEO-мета-теги | 24 часа | `seo_{type}_{slug}` |

---

## Дополнительные требования

### 1. Валидация

**Товар:**
- Технические характеристики должны соответствовать типам значений
- Артикул — уникальность

**Заказ:**
- Email — валидный формат
- Телефон — российский или международный формат
- Количество — целое положительное число

### 2. Уведомления

При создании заказа:
- Email уведомление менеджерам (Symfony Mailer)
- Telegram уведомление (Symfony Notifier)
- Запись в админку (в БД)

### 3. Безопасность

- CORS настройки для фронтенда
- Валидация всех входных данных
- Защита от SQL-инъекций через Doctrine
- Ограничение размера загружаемых файлов
- Санитизация HTML в описаниях

### 4. Документация

- Автоматическая OpenAPI документация через API Platform
- Доступна по `/api/docs`

---

## Критерии приёмки

1. **Функциональность:**
   - Дерево категорий строится корректно с любой вложенностью
   - Фильтрация товаров по техническим характеристикам работает
   - Поиск возвращает релевантные результаты
   - Заявка создается, уведомления отправляются
   - Корзина работает для неавторизованных пользователей
   - Все SEO-эндпоинты возвращают корректные данные

2. **Производительность:**
   - Дерево категорий (до 500 категорий) — не более 500ms
   - Поиск — не более 300ms
   - Фильтрация — не более 400ms
   - Создание заказа — не более 2 секунд

3. **SEO:**
   - Канонические URL генерируются для всех страниц
   - Мета-теги доступны через отдельный эндпоинт
   - ЧПУ-адреса поддерживаются

4. **Документация:**
   - OpenAPI документация доступна по `/api/docs`

---

## Примечания для разработчика

1. **Иерархия категорий:** Использовать `WITH RECURSIVE` для выборки дерева
2. **JSONB характеристики:** Использовать оператор `@>` для фильтрации
3. **Поиск:** Использовать `pg_trgm` для нечеткого поиска и ранжирования по релевантности
4. **Кэширование:** Symfony Cache с адаптером `FilesystemAdapter`
5. **Уведомления:** Использовать Symfony Mailer и Symfony Notifier для Telegram
6. **Корзина:** Хранить в сессии или в JWT для неавторизованных пользователей
7. **Артикулы:** Уникальность артикулов проверять на уровне БД