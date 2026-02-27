<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219232933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, lu TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, destinataire_id INT NOT NULL, logement_id INT DEFAULT NULL, INDEX IDX_BF5476CAA4F84F6E (destinataire_id), INDEX IDX_BF5476CA58ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA58ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD445776C50E4A FOREIGN KEY (proprietaire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user CHANGE rejection_reason rejection_reason LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA38A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA3858ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D13EDA38A76ED395 ON user_favoris (user_id)');
        $this->addSql('CREATE INDEX IDX_D13EDA3858ABF955 ON user_favoris (logement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA4F84F6E');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA58ABF955');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457BCF5E72D');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD445776C50E4A');
        $this->addSql('ALTER TABLE `user` CHANGE rejection_reason rejection_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA38A76ED395');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA3858ABF955');
        $this->addSql('DROP INDEX IDX_D13EDA38A76ED395 ON user_favoris');
        $this->addSql('DROP INDEX IDX_D13EDA3858ABF955 ON user_favoris');
    }
}
