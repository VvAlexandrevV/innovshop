<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426094220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant ADD price_modifier NUMERIC(10, 2) DEFAULT NULL, ADD is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE variant_value ADD variant_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE variant_value ADD CONSTRAINT FK_9DFDC769AAE9A1D5 FOREIGN KEY (variant_type_id) REFERENCES variant_type (id)');
        $this->addSql('CREATE INDEX IDX_9DFDC769AAE9A1D5 ON variant_value (variant_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant DROP price_modifier, DROP is_active');
        $this->addSql('ALTER TABLE variant_value DROP FOREIGN KEY FK_9DFDC769AAE9A1D5');
        $this->addSql('DROP INDEX IDX_9DFDC769AAE9A1D5 ON variant_value');
        $this->addSql('ALTER TABLE variant_value DROP variant_type_id');
    }
}
