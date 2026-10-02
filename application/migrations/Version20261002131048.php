<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002131048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Popins (popin, popin_image)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE popin (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, link VARCHAR(500) DEFAULT NULL, link_label VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, publish_at DATETIME DEFAULT NULL, publish_until DATETIME DEFAULT NULL, display_all TINYINT NOT NULL, urls JSON DEFAULT NULL, force_display TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE popin_image (title VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, attr_title VARCHAR(255) DEFAULT NULL, attr_alt VARCHAR(255) DEFAULT NULL, attr_class VARCHAR(255) DEFAULT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, popin_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_AF00BA7F6975F61A (popin_id), INDEX IDX_AF00BA7F3DA5256D (image_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE popin_image ADD CONSTRAINT FK_AF00BA7F3DA5256D FOREIGN KEY (image_id) REFERENCES aropixel_image (id)');
        $this->addSql('ALTER TABLE popin_image ADD CONSTRAINT FK_AF00BA7F6975F61A FOREIGN KEY (popin_id) REFERENCES popin (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE popin_image DROP FOREIGN KEY FK_AF00BA7F3DA5256D');
        $this->addSql('ALTER TABLE popin_image DROP FOREIGN KEY FK_AF00BA7F6975F61A');
        $this->addSql('DROP TABLE popin');
        $this->addSql('DROP TABLE popin_image');
    }
}
