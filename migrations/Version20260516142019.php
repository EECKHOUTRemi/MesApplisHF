<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop not null for measurements columns
 */
final class Version20260516142019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop not null for measurements columns';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE measurement ALTER chest DROP NOT NULL');
        $this->addSql('ALTER TABLE measurement ALTER hips DROP NOT NULL');
        $this->addSql('ALTER TABLE measurement ALTER thigh DROP NOT NULL');
        $this->addSql('ALTER TABLE measurement ALTER waist DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE measurement ALTER chest SET NOT NULL');
        $this->addSql('ALTER TABLE measurement ALTER hips SET NOT NULL');
        $this->addSql('ALTER TABLE measurement ALTER thigh SET NOT NULL');
        $this->addSql('ALTER TABLE measurement ALTER waist SET NOT NULL');
    }
}
