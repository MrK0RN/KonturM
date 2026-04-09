<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260329140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Media gallery: media_assets for product/category images with order and primary flag.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE media_assets (id UUID NOT NULL, owner_type VARCHAR(20) NOT NULL, owner_id UUID NOT NULL, path VARCHAR(500) NOT NULL, thumb_path VARCHAR(500) NOT NULL, sort_order INT DEFAULT 0 NOT NULL, is_primary BOOLEAN DEFAULT false NOT NULL, alt VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_media_owner ON media_assets (owner_type, owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE media_assets');
    }
}
