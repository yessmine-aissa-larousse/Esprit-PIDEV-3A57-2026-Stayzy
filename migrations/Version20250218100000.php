<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250218100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avis_count to post and comment, parent_id to comment for replies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD avis_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE comment ADD avis_count INT DEFAULT 0 NOT NULL, ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_9474526C727ACA70 ON comment (parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C727ACA70');
        $this->addSql('DROP INDEX IDX_9474526C727ACA70 ON comment');
        $this->addSql('ALTER TABLE comment DROP avis_count, DROP parent_id');
        $this->addSql('ALTER TABLE post DROP avis_count');
    }
}
