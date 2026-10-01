<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001153100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aropixel_admin_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, enabled TINYINT NOT NULL, initialized TINYINT NOT NULL, password_attempts INT NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, password_reset_token VARCHAR(255) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, email_verification_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, last_password_update DATETIME DEFAULT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_B6635904E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aropixel_admin_user_image (title VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, attr_title VARCHAR(255) DEFAULT NULL, attr_alt VARCHAR(255) DEFAULT NULL, attr_class VARCHAR(255) DEFAULT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9D7DD20A76ED395 (user_id), INDEX IDX_9D7DD203DA5256D (image_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aropixel_file (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, filename VARCHAR(255) NOT NULL, extension VARCHAR(20) NOT NULL, public TINYINT NOT NULL, import LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aropixel_image (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, attr_title VARCHAR(255) DEFAULT NULL, attr_alt VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, filename VARCHAR(255) NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, extension VARCHAR(20) NOT NULL, import LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aropixel_page (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, type VARCHAR(100) NOT NULL, title VARCHAR(255) DEFAULT NULL, sub_title VARCHAR(255) DEFAULT NULL, static_code VARCHAR(50) DEFAULT NULL, is_deletable TINYINT NOT NULL, excerpt LONGTEXT DEFAULT NULL, html_content LONGTEXT DEFAULT NULL, json_content LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, publish_at DATETIME DEFAULT NULL, publish_until DATETIME DEFAULT NULL, parent_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_87C59DCBECBB692D (static_code), INDEX IDX_87C59DCB8CDE5729 (type), INDEX IDX_87C59DCB727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aropixel_page_translation (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(20) NOT NULL, field VARCHAR(32) NOT NULL, content LONGTEXT DEFAULT NULL, object_id INT DEFAULT NULL, INDEX page_translation_idx (locale, object_id, field), INDEX IDX_45C39FBF232D562B (object_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE aropixel_admin_user_image ADD CONSTRAINT FK_9D7DD203DA5256D FOREIGN KEY (image_id) REFERENCES aropixel_image (id)');
        $this->addSql('ALTER TABLE aropixel_admin_user_image ADD CONSTRAINT FK_9D7DD20A76ED395 FOREIGN KEY (user_id) REFERENCES aropixel_admin_user (id)');
        $this->addSql('ALTER TABLE aropixel_page ADD CONSTRAINT FK_87C59DCB727ACA70 FOREIGN KEY (parent_id) REFERENCES aropixel_page (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE aropixel_page_translation ADD CONSTRAINT FK_45C39FBF232D562B FOREIGN KEY (object_id) REFERENCES aropixel_page (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aropixel_admin_user_image DROP FOREIGN KEY FK_9D7DD203DA5256D');
        $this->addSql('ALTER TABLE aropixel_admin_user_image DROP FOREIGN KEY FK_9D7DD20A76ED395');
        $this->addSql('ALTER TABLE aropixel_page DROP FOREIGN KEY FK_87C59DCB727ACA70');
        $this->addSql('ALTER TABLE aropixel_page_translation DROP FOREIGN KEY FK_45C39FBF232D562B');
        $this->addSql('DROP TABLE aropixel_admin_user');
        $this->addSql('DROP TABLE aropixel_admin_user_image');
        $this->addSql('DROP TABLE aropixel_file');
        $this->addSql('DROP TABLE aropixel_image');
        $this->addSql('DROP TABLE aropixel_page');
        $this->addSql('DROP TABLE aropixel_page_translation');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
