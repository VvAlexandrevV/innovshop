<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260501104041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item ADD commission_amount NUMERIC(10, 2) NOT NULL, ADD seller_amount NUMERIC(10, 2) NOT NULL, ADD platform_amount NUMERIC(10, 2) NOT NULL, ADD seller_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_52EA1F098DE820D9 ON order_item (seller_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098DE820D9');
        $this->addSql('DROP INDEX IDX_52EA1F098DE820D9 ON order_item');
        $this->addSql('ALTER TABLE order_item DROP commission_amount, DROP seller_amount, DROP platform_amount, DROP seller_id');
    }
}
