<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427121229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart_item_variant (cart_item_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_8C910B90E9B59A59 (cart_item_id), INDEX IDX_8C910B903B69A9AF (variant_id), PRIMARY KEY (cart_item_id, variant_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cart_item_variant ADD CONSTRAINT FK_8C910B90E9B59A59 FOREIGN KEY (cart_item_id) REFERENCES cart_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item_variant ADD CONSTRAINT FK_8C910B903B69A9AF FOREIGN KEY (variant_id) REFERENCES variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY `FK_F0FE25273B69A9AF`');
        $this->addSql('DROP INDEX IDX_F0FE25273B69A9AF ON cart_item');
        $this->addSql('ALTER TABLE cart_item DROP variant_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_item_variant DROP FOREIGN KEY FK_8C910B90E9B59A59');
        $this->addSql('ALTER TABLE cart_item_variant DROP FOREIGN KEY FK_8C910B903B69A9AF');
        $this->addSql('DROP TABLE cart_item_variant');
        $this->addSql('ALTER TABLE cart_item ADD variant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT `FK_F0FE25273B69A9AF` FOREIGN KEY (variant_id) REFERENCES variant (id)');
        $this->addSql('CREATE INDEX IDX_F0FE25273B69A9AF ON cart_item (variant_id)');
    }
}
