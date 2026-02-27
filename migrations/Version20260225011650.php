<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225011650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, prix_total DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, payment_methode VARCHAR(255) DEFAULT NULL, payment_status VARCHAR(255) DEFAULT NULL, transaction_id VARCHAR(255) DEFAULT NULL, date_transaction DATETIME DEFAULT NULL, reservation_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_6EEAA67DB83297E7 (reservation_id), INDEX IDX_6EEAA67DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE logement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, adresse JSON DEFAULT NULL, prix DOUBLE PRECISION NOT NULL, superficie INT NOT NULL, nombre_chambres INT NOT NULL, nombre_salle_de_bain INT NOT NULL, amenites JSON DEFAULT NULL, disponible TINYINT(1) NOT NULL, photos JSON DEFAULT NULL, photo_principale VARCHAR(255) DEFAULT NULL, note_moyenne DOUBLE PRECISION DEFAULT NULL, total_avis INT DEFAULT NULL, created_at DATETIME NOT NULL, categorie_id INT NOT NULL, proprietaire_id INT NOT NULL, INDEX IDX_F0FD4457BCF5E72D (categorie_id), INDEX IDX_F0FD445776C50E4A (proprietaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, lu TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, destinataire_id INT NOT NULL, logement_id INT DEFAULT NULL, INDEX IDX_BF5476CAA4F84F6E (destinataire_id), INDEX IDX_BF5476CA58ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE promotion (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(100) NOT NULL, pourcentage INT NOT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, code_promo VARCHAR(30) DEFAULT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, logement_id INT NOT NULL, INDEX IDX_C11D7DD158ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, nombre_personnes INT NOT NULL, prix_total DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, message_demande LONGTEXT DEFAULT NULL, user_id INT NOT NULL, logement_id INT NOT NULL, INDEX IDX_42C84955A76ED395 (user_id), INDEX IDX_42C8495558ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, tel VARCHAR(20) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, image_url VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, last_login DATETIME DEFAULT NULL, is_verified TINYINT(1) NOT NULL, approval_status VARCHAR(20) DEFAULT NULL, approval_date DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_favoris (user_id INT NOT NULL, logement_id INT NOT NULL, INDEX IDX_D13EDA38A76ED395 (user_id), INDEX IDX_D13EDA3858ABF955 (logement_id), PRIMARY KEY(user_id, logement_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD445776C50E4A FOREIGN KEY (proprietaire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA58ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE promotion ADD CONSTRAINT FK_C11D7DD158ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA38A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favoris ADD CONSTRAINT FK_D13EDA3858ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DB83297E7');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457BCF5E72D');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD445776C50E4A');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA4F84F6E');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA58ABF955');
        $this->addSql('ALTER TABLE promotion DROP FOREIGN KEY FK_C11D7DD158ABF955');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495558ABF955');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA38A76ED395');
        $this->addSql('ALTER TABLE user_favoris DROP FOREIGN KEY FK_D13EDA3858ABF955');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE promotion');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_favoris');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
