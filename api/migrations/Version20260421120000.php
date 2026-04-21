<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer_inn to orders (checkout ИНН).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_inn VARCHAR(12) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP COLUMN IF EXISTS customer_inn');
    }
}
