<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227052848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, lu TINYINT NOT NULL, created_at DATETIME NOT NULL, destinataire_id INT NOT NULL, logement_id INT DEFAULT NULL, INDEX IDX_BF5476CAA4F84F6E (destinataire_id), INDEX IDX_BF5476CA58ABF955 (logement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE promotion (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(100) NOT NULL, pourcentage INT NOT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, code_promo VARCHAR(30) DEFAULT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, logement_id INT NOT NULL, INDEX IDX_C11D7DD158ABF955 (logement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_favoris (user_id INT NOT NULL, logement_id INT NOT NULL, INDEX IDX_D13EDA38A76ED395 (user_id), INDEX IDX_D13EDA3858ABF955 (logement_id), PRIMARY KEY (user_id, logement_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA58ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE promotion ADD CONSTRAINT FK_C11D7DD158ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA38A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA3858ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande CHANGE reservation_id reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE comment CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE logement ADD created_at DATETIME NOT NULL, ADD categorie_id INT NOT NULL, ADD proprietaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD445776C50E4A FOREIGN KEY (proprietaire_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_F0FD4457BCF5E72D ON logement (categorie_id)');
        $this->addSql('CREATE INDEX IDX_F0FD445776C50E4A ON logement (proprietaire_id)');
        $this->addSql('ALTER TABLE post CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE user ADD approval_status VARCHAR(20) DEFAULT NULL, ADD approval_date DATETIME DEFAULT NULL, ADD rejection_reason LONGTEXT DEFAULT NULL, ADD profile_picture VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA4F84F6E');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA58ABF955');
        $this->addSql('ALTER TABLE promotion DROP FOREIGN KEY FK_C11D7DD158ABF955');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA38A76ED395');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA3858ABF955');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE promotion');
        $this->addSql('DROP TABLE user_favoris');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DB83297E7');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande CHANGE reservation_id reservation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457BCF5E72D');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD445776C50E4A');
        $this->addSql('DROP INDEX IDX_F0FD4457BCF5E72D ON logement');
        $this->addSql('DROP INDEX IDX_F0FD445776C50E4A ON logement');
        $this->addSql('ALTER TABLE logement DROP created_at, DROP categorie_id, DROP proprietaire_id');
        $this->addSql('ALTER TABLE post CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495558ABF955');
        $this->addSql('ALTER TABLE `user` DROP approval_status, DROP approval_date, DROP rejection_reason, DROP profile_picture');
    }
}
