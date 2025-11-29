<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create filesync_rate_entries table (store one row per request for rolling 24h rate limiting)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE filesync_rate_entries (
  id INT AUTO_INCREMENT NOT NULL,
  token_id INT NOT NULL,
  requested_at DATETIME NOT NULL,
  INDEX IDX_FILESYNCRATE_TOKEN (token_id),
  PRIMARY KEY(id),
  CONSTRAINT FK_FILESYNCRATE_TOKEN FOREIGN KEY (token_id) REFERENCES filesync_tokens (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS filesync_rate_entries');
    }
}