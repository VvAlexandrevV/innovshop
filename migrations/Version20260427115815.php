<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427115815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE variant_product DROP FOREIGN KEY `FK_BCEF29EB3B69A9AF`');
        $this->addSql('ALTER TABLE variant_product DROP FOREIGN KEY `FK_BCEF29EB4584665A`');
        $this->addSql('DROP TABLE variant_product');
        $this->addSql('ALTER TABLE product_variant_link DROP PRIMARY KEY, ADD PRIMARY KEY (variant_id, product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE variant_product (variant_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_BCEF29EB4584665A (product_id), INDEX IDX_BCEF29EB3B69A9AF (variant_id), PRIMARY KEY (variant_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE variant_product ADD CONSTRAINT `FK_BCEF29EB3B69A9AF` FOREIGN KEY (variant_id) REFERENCES variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variant_product ADD CONSTRAINT `FK_BCEF29EB4584665A` FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variant_link DROP PRIMARY KEY, ADD PRIMARY KEY (product_id, variant_id)');
    }
}
