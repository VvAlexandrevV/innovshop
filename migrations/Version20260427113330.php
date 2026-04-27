<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427113330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_variant_link (product_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_54F02D0F4584665A (product_id), INDEX IDX_54F02D0F3B69A9AF (variant_id), PRIMARY KEY (product_id, variant_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE product_variant_link ADD CONSTRAINT FK_54F02D0F4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variant_link ADD CONSTRAINT FK_54F02D0F3B69A9AF FOREIGN KEY (variant_id) REFERENCES variant (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE product_variant');
        $this->addSql('ALTER TABLE order_item ADD variant_label VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_variant (variant_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_209AA41D4584665A (product_id), PRIMARY KEY (product_id, variant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT `FK_209AA41D4584665A` FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variant_link DROP FOREIGN KEY FK_54F02D0F4584665A');
        $this->addSql('ALTER TABLE product_variant_link DROP FOREIGN KEY FK_54F02D0F3B69A9AF');
        $this->addSql('DROP TABLE product_variant_link');
        $this->addSql('ALTER TABLE order_item DROP variant_label');
    }
}
