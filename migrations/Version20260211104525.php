<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211104525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration initiale : création de toutes les tables principales, notifications et promotions';
    }

    public function up(Schema $schema): void
    {
        // Création des tables de base
        $this->addSql('CREATE TABLE user (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            nom VARCHAR(50) NOT NULL,
            prenom VARCHAR(50) NOT NULL,
            tel VARCHAR(20) DEFAULT NULL,
            adresse VARCHAR(255) DEFAULT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            last_login DATETIME DEFAULT NULL,
            is_verified TINYINT(1) NOT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE logement (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            adresse JSON DEFAULT NULL,
            prix DOUBLE PRECISION NOT NULL,
            superficie INT NOT NULL,
            nombre_chambres INT NOT NULL,
            nombre_salle_de_bain INT NOT NULL,
            amenites JSON DEFAULT NULL,
            disponible TINYINT(1) NOT NULL,
            photos JSON DEFAULT NULL,
            photo_principale VARCHAR(255) DEFAULT NULL,
            note_moyenne DOUBLE PRECISION DEFAULT NULL,
            total_avis INT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE reservation (
            id INT AUTO_INCREMENT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            nombre_personnes INT NOT NULL,
            prix_total DOUBLE PRECISION NOT NULL,
            status VARCHAR(255) NOT NULL,
            message_demande LONGTEXT DEFAULT NULL,
            user_id INT NOT NULL,
            logement_id INT NOT NULL,
            INDEX IDX_42C84955A76ED395 (user_id),
            INDEX IDX_42C8495558ABF955 (logement_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE commande (
            id INT AUTO_INCREMENT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            prix_total DOUBLE PRECISION NOT NULL,
            status VARCHAR(255) NOT NULL,
            payment_methode VARCHAR(255) DEFAULT NULL,
            payment_status VARCHAR(255) DEFAULT NULL,
            transaction_id VARCHAR(255) DEFAULT NULL,
            date_transaction DATETIME DEFAULT NULL,
            reservation_id INT DEFAULT NULL,
            user_id INT DEFAULT NULL,
            INDEX IDX_6EEAA67DB83297E7 (reservation_id),
            INDEX IDX_6EEAA67DA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE messenger_messages (
            id BIGINT AUTO_INCREMENT NOT NULL,
            body LONGTEXT NOT NULL,
            headers LONGTEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL,
            available_at DATETIME NOT NULL,
            delivered_at DATETIME DEFAULT NULL,
            INDEX IDX_QUEUE_NAME (queue_name, available_at, delivered_at, id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE notifications (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            message LONGTEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX IDX_notifications_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE promotions (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            logement_id INT NOT NULL,
            INDEX IDX_promotions_logement (logement_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4');

        // Clés étrangères
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_notifications_user FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE promotions ADD CONSTRAINT FK_promotions_logement FOREIGN KEY (logement_id) REFERENCES logement (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DB83297E7');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495558ABF955');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_notifications_user');
        $this->addSql('ALTER TABLE promotions DROP FOREIGN KEY FK_promotions_logement');

        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE promotions');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}