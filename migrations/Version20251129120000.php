<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create filesync_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE filesync_tokens (
  id INT AUTO_INCREMENT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  last_chars VARCHAR(8) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS filesync_tokens');
    }
}