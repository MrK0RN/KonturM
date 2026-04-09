<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260329120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add categories.filter_config (JSON) for per-category filter whitelist, order and labels.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories ADD filter_config JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categories DROP filter_config');
    }
}
