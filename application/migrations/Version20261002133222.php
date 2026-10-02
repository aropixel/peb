<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002133222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dons (donation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE donation (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, amount INT NOT NULL, currency VARCHAR(3) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, message LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, payment_method VARCHAR(50) DEFAULT NULL, paid_at DATETIME DEFAULT NULL, amount_refunded INT NOT NULL, refunded_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_31E581A0AEA34913 (reference), UNIQUE INDEX UNIQ_31E581A05A18FBC7 (stripe_checkout_session_id), INDEX IDX_31E581A07B00651C (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE donation');
    }
}
