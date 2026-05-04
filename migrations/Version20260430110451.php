<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260430110451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seller_profile (id INT AUTO_INCREMENT NOT NULL, company_name VARCHAR(255) NOT NULL, siret VARCHAR(14) NOT NULL, company_email VARCHAR(255) NOT NULL, company_phone VARCHAR(30) DEFAULT NULL, company_address VARCHAR(255) NOT NULL, company_postal_code VARCHAR(255) NOT NULL, company_city VARCHAR(100) NOT NULL, company_country VARCHAR(100) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, stripe_account_id VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_5A59131EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE seller_profile ADD CONSTRAINT FK_5A59131EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seller_profile DROP FOREIGN KEY FK_5A59131EA76ED395');
        $this->addSql('DROP TABLE seller_profile');
    }
}
