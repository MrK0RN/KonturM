<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323024106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categories, products, services, orders tables with required indexes (pg_trgm + JSONB GIN).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        $this->addSql(<<<SQL
CREATE TABLE categories (
    id UUID NOT NULL,
    parent_id UUID DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    photo VARCHAR(500) DEFAULT NULL,
    photo_alt VARCHAR(255) DEFAULT NULL,
    is_favorite_main BOOLEAN NOT NULL DEFAULT FALSE,
    is_favorite_sidebar BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INTEGER NOT NULL DEFAULT 0,
    display_mode VARCHAR(50) NOT NULL DEFAULT 'subcategories_only',
    aggregate_products BOOLEAN NOT NULL DEFAULT FALSE,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY(id),
    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);
SQL);

        $this->addSql('CREATE UNIQUE INDEX idx_categories_slug ON categories (slug);');
        $this->addSql('CREATE INDEX idx_categories_parent_id ON categories (parent_id);');
        $this->addSql('CREATE INDEX idx_categories_is_favorite_main ON categories (is_favorite_main);');
        $this->addSql('CREATE INDEX idx_categories_is_favorite_sidebar ON categories (is_favorite_sidebar);');
        $this->addSql('CREATE INDEX idx_categories_sort_order ON categories (sort_order);');

        $this->addSql(<<<SQL
CREATE TABLE products (
    id UUID NOT NULL,
    category_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    article VARCHAR(100) DEFAULT NULL,
    photo VARCHAR(500) DEFAULT NULL,
    photo_alt VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    technical_specs JSONB DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT NULL,
    stock_status VARCHAR(50) NOT NULL DEFAULT 'on_order',
    manufacturing_time VARCHAR(100) DEFAULT NULL,
    gost_number VARCHAR(100) DEFAULT NULL,
    has_verification BOOLEAN NOT NULL DEFAULT FALSE,
    drawings JSONB DEFAULT NULL,
    documents JSONB DEFAULT NULL,
    certificates JSONB DEFAULT NULL,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY(id),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
SQL);

        $this->addSql('CREATE UNIQUE INDEX idx_products_slug ON products (slug);');
        $this->addSql('CREATE UNIQUE INDEX idx_products_article ON products (article);');

        $this->addSql(<<<SQL
CREATE TABLE services (
    id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT NULL,
    price_type VARCHAR(50) NOT NULL DEFAULT 'fixed',
    photo VARCHAR(500) DEFAULT NULL,
    requires_technical_spec BOOLEAN NOT NULL DEFAULT FALSE,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY(id)
);
SQL);

        $this->addSql('CREATE UNIQUE INDEX idx_services_slug ON services (slug);');
        $this->addSql('CREATE INDEX idx_services_sort_order ON services (sort_order);');

        $this->addSql(<<<SQL
CREATE TABLE orders (
    id UUID NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_company VARCHAR(255) DEFAULT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    items JSONB NOT NULL,
    attachments JSONB DEFAULT NULL,
    comment TEXT DEFAULT NULL,
    total_amount DECIMAL(12,2) DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY(id)
);
SQL);

        $this->addSql('CREATE UNIQUE INDEX idx_orders_order_number ON orders (order_number);');
        $this->addSql('CREATE INDEX idx_orders_status ON orders (status);');
        $this->addSql('CREATE INDEX idx_orders_created_at ON orders (created_at);');
        $this->addSql('CREATE INDEX idx_orders_customer_email ON orders (customer_email);');

        // --- PostgreSQL indexes from PROMPT.md ---
        $this->addSql('CREATE INDEX idx_products_technical_specs ON products USING GIN (technical_specs);');
        $this->addSql('CREATE INDEX idx_products_drawings ON products USING GIN (drawings);');
        $this->addSql('CREATE INDEX idx_products_documents ON products USING GIN (documents);');
        $this->addSql('CREATE INDEX idx_products_certificates ON products USING GIN (certificates);');
        $this->addSql('CREATE INDEX idx_orders_items ON orders USING GIN (items);');
        $this->addSql('CREATE INDEX idx_orders_attachments ON orders USING GIN (attachments);');

        $this->addSql('CREATE INDEX idx_products_name_trgm ON products USING GIN (name gin_trgm_ops);');
        $this->addSql('CREATE INDEX idx_products_description_trgm ON products USING GIN (description gin_trgm_ops);');
        $this->addSql('CREATE INDEX idx_products_article_trgm ON products USING GIN (article gin_trgm_ops);');
        $this->addSql('CREATE INDEX idx_products_gost_number_trgm ON products USING GIN (gost_number gin_trgm_ops);');
        $this->addSql('CREATE INDEX idx_services_name_trgm ON services USING GIN (name gin_trgm_ops);');
        $this->addSql('CREATE INDEX idx_categories_name_trgm ON categories USING GIN (name gin_trgm_ops);');

        $this->addSql('CREATE INDEX idx_products_category_id ON products (category_id);');
        $this->addSql('CREATE INDEX idx_products_price ON products (price);');
        $this->addSql('CREATE INDEX idx_products_has_verification ON products (has_verification);');
        $this->addSql('CREATE INDEX idx_products_stock_status ON products (stock_status);');
        $this->addSql('CREATE INDEX idx_products_created_at ON products (created_at);');
    }

    public function down(Schema $schema): void
    {
        // Drop in reverse order of dependencies.
        $this->addSql('DROP TABLE IF EXISTS orders;');
        $this->addSql('DROP TABLE IF EXISTS services;');
        $this->addSql('DROP TABLE IF EXISTS products;');
        $this->addSql('DROP TABLE IF EXISTS categories;');
    }
}

