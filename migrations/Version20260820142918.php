<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make description required again (reverts Version20260623234721): a recipe
 * must always have a description, enforced by Assert\NotBlank on the entity.
 */
final class Version20260820142918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make description required again';
    }

    public function up(Schema $schema): void
    {
        // Backfill existing rows saved while description was optional, so the
        // NOT NULL constraint below doesn't fail against legacy data.
        $this->addSql("UPDATE recipe SET description = '' WHERE description IS NULL");
        $this->addSql('ALTER TABLE recipe ALTER description SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe ALTER description DROP NOT NULL');
    }
}
