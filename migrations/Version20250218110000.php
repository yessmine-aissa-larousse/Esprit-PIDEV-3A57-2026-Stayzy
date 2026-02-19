<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250218110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dislike_count to post and comment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD dislike_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE comment ADD dislike_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP dislike_count');
        $this->addSql('ALTER TABLE comment DROP dislike_count');
    }
}
