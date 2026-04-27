<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426113645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant_value ADD product_variant_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_variant_value ADD CONSTRAINT FK_4710DB9BA80EF684 FOREIGN KEY (product_variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_4710DB9BA80EF684 ON product_variant_value (product_variant_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant_value DROP FOREIGN KEY FK_4710DB9BA80EF684');
        $this->addSql('DROP INDEX IDX_4710DB9BA80EF684 ON product_variant_value');
        $this->addSql('ALTER TABLE product_variant_value DROP product_variant_id');
    }
}
