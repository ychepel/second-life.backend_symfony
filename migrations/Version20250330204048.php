<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250330204048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE bid (id INT AUTO_INCREMENT NOT NULL, bid_value NUMERIC(7, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id BIGINT NOT NULL, offer_id INT NOT NULL, INDEX IDX_4AF2B3F3A76ED395 (user_id), INDEX IDX_4AF2B3F353C674EE (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, is_active TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_64C19C15E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE confirmation_code (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(36) NOT NULL, expired DATETIME NOT NULL, user_id BIGINT NOT NULL, UNIQUE INDEX UNIQ_A0E239DE77153098 (code), INDEX IDX_A0E239DEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, entity_id INT NOT NULL, entity_type VARCHAR(64) NOT NULL, size VARCHAR(64) NOT NULL, base_name VARCHAR(64) NOT NULL, full_path VARCHAR(256) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, UNIQUE INDEX UNIQ_5E9E89CB5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, authenticated_user_id INT NOT NULL, receiver_role VARCHAR(20) NOT NULL, context_id INT DEFAULT NULL, notification_type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE offer (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(64) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, auction_duration_days INT NOT NULL, start_price NUMERIC(7, 2) DEFAULT NULL, win_bid NUMERIC(7, 2) DEFAULT NULL, is_free TINYINT(1) NOT NULL, auction_finished_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, user_id BIGINT NOT NULL, status_id INT NOT NULL, category_id INT NOT NULL, winner_bid_id INT DEFAULT NULL, location_id INT DEFAULT NULL, INDEX IDX_29D6873EA76ED395 (user_id), INDEX IDX_29D6873E6BF700BD (status_id), INDEX IDX_29D6873E12469DE2 (category_id), INDEX IDX_29D6873E61C2EF79 (winner_bid_id), INDEX IDX_29D6873E64D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE offer_status_history (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, created_at DATETIME NOT NULL, status_id INT NOT NULL, rejection_reason_id INT DEFAULT NULL, INDEX IDX_B8E48A156BF700BD (status_id), INDEX IDX_B8E48A1551BD4E15 (rejection_reason_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rejection_reasons (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (id BIGINT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(60) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, role VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, location_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E935C246D5 (password), INDEX IDX_1483A5E964D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bid ADD CONSTRAINT FK_4AF2B3F3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bid ADD CONSTRAINT FK_4AF2B3F353C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE confirmation_code ADD CONSTRAINT FK_A0E239DEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer ADD CONSTRAINT FK_29D6873EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer ADD CONSTRAINT FK_29D6873E6BF700BD FOREIGN KEY (status_id) REFERENCES status (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer ADD CONSTRAINT FK_29D6873E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer ADD CONSTRAINT FK_29D6873E61C2EF79 FOREIGN KEY (winner_bid_id) REFERENCES bid (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer ADD CONSTRAINT FK_29D6873E64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer_status_history ADD CONSTRAINT FK_B8E48A156BF700BD FOREIGN KEY (status_id) REFERENCES status (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer_status_history ADD CONSTRAINT FK_B8E48A1551BD4E15 FOREIGN KEY (rejection_reason_id) REFERENCES rejection_reasons (id) ON DELETE SET NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD CONSTRAINT FK_1483A5E964D218E FOREIGN KEY (location_id) REFERENCES location (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE bid DROP FOREIGN KEY FK_4AF2B3F3A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bid DROP FOREIGN KEY FK_4AF2B3F353C674EE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE confirmation_code DROP FOREIGN KEY FK_A0E239DEA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer DROP FOREIGN KEY FK_29D6873EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E6BF700BD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E12469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E61C2EF79
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E64D218E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer_status_history DROP FOREIGN KEY FK_B8E48A156BF700BD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offer_status_history DROP FOREIGN KEY FK_B8E48A1551BD4E15
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP FOREIGN KEY FK_1483A5E964D218E
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bid
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE confirmation_code
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE image
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE location
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE offer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE offer_status_history
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rejection_reasons
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE status
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
    }
}
