<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428112147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace la relation ManyToMany Product-Variant par une relation ManyToOne Variant-Product avec stock.';
    }

  public function up(Schema $schema): void
    {
        $this->addSql('
            DELETE v
            FROM variant v
            LEFT JOIN product p ON p.id = v.product_id
            WHERE v.product_id IS NULL OR p.id IS NULL
        ');

        $this->addSql('ALTER TABLE variant CHANGE product_id product_id INT NOT NULL');

        $this->addSql('CREATE INDEX IDX_F143BFAD4584665A ON variant (product_id)');

        $this->addSql('ALTER TABLE variant ADD CONSTRAINT FK_F143BFAD4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_variant_link (product_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_54F02D0F3B69A9AF (variant_id), INDEX IDX_54F02D0F4584665A (product_id), PRIMARY KEY (variant_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('INSERT INTO product_variant_link (product_id, variant_id) SELECT product_id, id FROM variant');

        $this->addSql('ALTER TABLE product_variant_link ADD CONSTRAINT `FK_54F02D0F3B69A9AF` FOREIGN KEY (variant_id) REFERENCES variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variant_link ADD CONSTRAINT `FK_54F02D0F4584665A` FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE variant DROP FOREIGN KEY FK_F143BFAD4584665A');
        $this->addSql('DROP INDEX IDX_F143BFAD4584665A ON variant');
        $this->addSql('ALTER TABLE variant DROP stock, DROP product_id');
    }
}