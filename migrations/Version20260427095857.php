<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427095857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE variant (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, price_modifier NUMERIC(10, 2) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE variant_product (variant_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_BCEF29EB3B69A9AF (variant_id), INDEX IDX_BCEF29EB4584665A (product_id), PRIMARY KEY (variant_id, product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE variant_product ADD CONSTRAINT FK_BCEF29EB3B69A9AF FOREIGN KEY (variant_id) REFERENCES variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variant_product ADD CONSTRAINT FK_BCEF29EB4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE variant_product DROP FOREIGN KEY FK_BCEF29EB3B69A9AF');
        $this->addSql('ALTER TABLE variant_product DROP FOREIGN KEY FK_BCEF29EB4584665A');
        $this->addSql('DROP TABLE variant');
        $this->addSql('DROP TABLE variant_product');
    }
}
