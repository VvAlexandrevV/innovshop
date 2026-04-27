<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426092605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_variant (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, INDEX IDX_209AA41D4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_variant_value (id INT AUTO_INCREMENT NOT NULL, product_variant_id INT NOT NULL, variant_value_id INT NOT NULL, INDEX IDX_4710DB9BA80EF684 (product_variant_id), INDEX IDX_4710DB9B66F0FA2A (variant_value_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE variant_type (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE variant_value (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_variant_value ADD CONSTRAINT FK_4710DB9BA80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id)');
        $this->addSql('ALTER TABLE product_variant_value ADD CONSTRAINT FK_4710DB9B66F0FA2A FOREIGN KEY (variant_value_id) REFERENCES variant_value (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D4584665A');
        $this->addSql('ALTER TABLE product_variant_value DROP FOREIGN KEY FK_4710DB9BA80EF684');
        $this->addSql('ALTER TABLE product_variant_value DROP FOREIGN KEY FK_4710DB9B66F0FA2A');
        $this->addSql('DROP TABLE product_variant');
        $this->addSql('DROP TABLE product_variant_value');
        $this->addSql('DROP TABLE variant_type');
        $this->addSql('DROP TABLE variant_value');
    }
}
