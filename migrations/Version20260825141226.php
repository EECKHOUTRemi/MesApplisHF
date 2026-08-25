<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make ingredient deletable even if it\'s still used
 */
final class Version20260825141226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make ingredient deletable even if it\'s still used';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ref_recipe_ingredient DROP CONSTRAINT fk_f6bc88a0933fe08c');
        $this->addSql('ALTER TABLE ref_recipe_ingredient ADD CONSTRAINT FK_F6BC88A0933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ref_recipe_ingredient DROP CONSTRAINT FK_F6BC88A0933FE08C');
        $this->addSql('ALTER TABLE ref_recipe_ingredient ADD CONSTRAINT fk_f6bc88a0933fe08c FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
