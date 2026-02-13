<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250212000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Post and Comment entities for forum';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE post (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            excerpt VARCHAR(500) DEFAULT NULL,
            author VARCHAR(180) NOT NULL,
            is_published TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_post_created_at (created_at),
            INDEX idx_post_published (is_published),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE comment (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            author VARCHAR(180) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_comment_created_at (created_at),
            INDEX IDX_9474526C4B89032C (post_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE post');
    }
}
