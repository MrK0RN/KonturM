<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Page visits log for storefront statistics.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE page_visits (id UUID NOT NULL, path VARCHAR(2048) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_page_visits_created_at ON page_visits (created_at)');
        $this->addSql('CREATE INDEX idx_page_visits_path ON page_visits (path)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE page_visits');
    }
}
