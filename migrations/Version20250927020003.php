<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250927020003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_items ADD order_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_items ADD quantity INT NOT NULL');
        $this->addSql('ALTER TABLE order_items ADD unit_price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE order_items ADD line_total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_62809DB08D9F6D38 ON order_items (order_id)');
        $this->addSql('ALTER TABLE orders ADD shipping_address_id INT NOT NULL');
        $this->addSql('ALTER TABLE orders ADD order_number VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD subtotal NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD tax_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD shipping_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD total_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE orders ADD shipped_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE orders ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('COMMENT ON COLUMN orders.shipped_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN orders.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN orders.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN orders.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4D4CFF2B FOREIGN KEY (shipping_address_id) REFERENCES addresses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEE551F0F81 ON orders (order_number)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE4D4CFF2B ON orders (shipping_address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE order_items DROP CONSTRAINT FK_62809DB08D9F6D38');
        $this->addSql('DROP INDEX IDX_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP order_id');
        $this->addSql('ALTER TABLE order_items DROP quantity');
        $this->addSql('ALTER TABLE order_items DROP unit_price');
        $this->addSql('ALTER TABLE order_items DROP line_total');
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEE4D4CFF2B');
        $this->addSql('DROP INDEX UNIQ_E52FFDEE551F0F81');
        $this->addSql('DROP INDEX IDX_E52FFDEE4D4CFF2B');
        $this->addSql('ALTER TABLE orders DROP shipping_address_id');
        $this->addSql('ALTER TABLE orders DROP order_number');
        $this->addSql('ALTER TABLE orders DROP subtotal');
        $this->addSql('ALTER TABLE orders DROP tax_amount');
        $this->addSql('ALTER TABLE orders DROP shipping_amount');
        $this->addSql('ALTER TABLE orders DROP total_amount');
        $this->addSql('ALTER TABLE orders DROP status');
        $this->addSql('ALTER TABLE orders DROP shipped_at');
        $this->addSql('ALTER TABLE orders DROP delivered_at');
        $this->addSql('ALTER TABLE orders DROP created_at');
        $this->addSql('ALTER TABLE orders DROP updated_at');
    }
}
