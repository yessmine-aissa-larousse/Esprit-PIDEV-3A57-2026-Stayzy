<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513231322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY fk_reclamation_user');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY fk_reponse_reclamation');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY fk_reponse_user');
        $this->addSql('ALTER TABLE visite DROP FOREIGN KEY fk_visite_client');
        $this->addSql('ALTER TABLE visite DROP FOREIGN KEY fk_visite_logement');
        $this->addSql('ALTER TABLE visite DROP FOREIGN KEY fk_visite_proprietaire');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE reclamation');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE visite');
        $this->addSql('ALTER TABLE commande CHANGE prix_total prix_total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE logement CHANGE adresse adresse LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD445776C50E4A FOREIGN KEY (proprietaire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA58ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE promotion ADD CONSTRAINT FK_C11D7DD158ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation CHANGE prix_total prix_total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE user DROP email_verification_token');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA38A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA3858ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, logement_id INT NOT NULL, user_id INT NOT NULL, note INT NOT NULL, commentaire TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, photo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE INDEX unique_avis (logement_id, user_id), INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, author VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, image TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, avis_count INT DEFAULT 0 NOT NULL, dislike_count INT DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, post_id INT NOT NULL, parent_id INT DEFAULT NULL, signaled TINYINT(1) DEFAULT 0 NOT NULL, reactions BLOB DEFAULT NULL, INDEX fk_comment_post (post_id), INDEX fk_comment_parent (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, excerpt VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, author VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, image TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, avis_count INT DEFAULT 0 NOT NULL, dislike_count INT DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, hashtags VARCHAR(1000) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, signaled TINYINT(1) DEFAULT 0 NOT NULL, reactions BLOB DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reclamation (id INT AUTO_INCREMENT NOT NULL, sujet VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_reclamation DATETIME NOT NULL, statut VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, risk_score INT DEFAULT NULL, is_abusive TINYINT(1) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_reclamation_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_reponse DATETIME NOT NULL, reclamation_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_5FB6DEC72D6BA2D9 (reclamation_id), INDEX fk_reponse_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE visite (id INT AUTO_INCREMENT NOT NULL, date_visite DATE NOT NULL, heure_visite TIME NOT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, client_id INT NOT NULL, proprietaire_id INT NOT NULL, logement_id INT NOT NULL, INDEX IDX_visite_client (client_id), INDEX IDX_visite_proprietaire (proprietaire_id), INDEX IDX_visite_logement (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT fk_reclamation_user FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT fk_reponse_reclamation FOREIGN KEY (reclamation_id) REFERENCES reclamation (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT fk_reponse_user FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE visite ADD CONSTRAINT fk_visite_client FOREIGN KEY (client_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE visite ADD CONSTRAINT fk_visite_logement FOREIGN KEY (logement_id) REFERENCES logement (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE visite ADD CONSTRAINT fk_visite_proprietaire FOREIGN KEY (proprietaire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DB83297E7');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande CHANGE prix_total prix_total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457BCF5E72D');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD445776C50E4A');
        $this->addSql('ALTER TABLE logement CHANGE adresse adresse JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA4F84F6E');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA58ABF955');
        $this->addSql('ALTER TABLE promotion DROP FOREIGN KEY FK_C11D7DD158ABF955');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495558ABF955');
        $this->addSql('ALTER TABLE reservation CHANGE prix_total prix_total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD email_verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA38A76ED395');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA3858ABF955');
    }
}
