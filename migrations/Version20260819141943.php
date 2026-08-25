<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add portions, time, difficulty and cost to recipes
 */
final class Version20260819141943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add portions, time, difficulty and cost to recipes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recipe ADD portions INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recipe ADD time INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recipe ADD difficulty INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recipe ADD cost INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recipe DROP portions');
        $this->addSql('ALTER TABLE recipe DROP time');
        $this->addSql('ALTER TABLE recipe DROP difficulty');
        $this->addSql('ALTER TABLE recipe DROP cost');
    }
}
