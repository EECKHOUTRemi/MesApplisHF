<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Allow decimal recipe portions (e.g. 1.5)
 */
final class Version20261119100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow decimal recipe portions (e.g. 1.5)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe ALTER portions TYPE DOUBLE PRECISION');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipe ALTER portions TYPE INT USING ROUND(portions)::INT');
    }
}
