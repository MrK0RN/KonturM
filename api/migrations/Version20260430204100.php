<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430204100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category product recommendations for product detail cross-sell section.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories ADD COLUMN IF NOT EXISTS also_bought_product_ids JSONB DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_categories_also_bought_product_ids ON categories USING GIN (also_bought_product_ids)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_categories_also_bought_product_ids');
        $this->addSql('ALTER TABLE categories DROP COLUMN IF EXISTS also_bought_product_ids');
    }
}
