<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update deletion and null rules
 */
final class Version20260811094754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update deletion and null rules';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bmi DROP CONSTRAINT fk_502f0a4aa76ed395');
        $this->addSql('ALTER TABLE bmi ADD CONSTRAINT FK_502F0A4AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT fk_64c19c1b03a8386');
        $this->addSql('ALTER TABLE category ALTER created_by_id DROP NOT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE measurement DROP CONSTRAINT fk_2ce0d811a76ed395');
        $this->addSql('ALTER TABLE measurement ADD CONSTRAINT FK_2CE0D811A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT fk_b6bd307ff675f31b');
        $this->addSql('ALTER TABLE message ALTER author_id DROP NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE recipe DROP CONSTRAINT fk_da88b137f675f31b');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT FK_DA88B137F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE ref_recipe_ingredient DROP CONSTRAINT fk_f6bc88a059d8a214');
        $this->addSql('ALTER TABLE ref_recipe_ingredient ADD CONSTRAINT FK_F6BC88A059D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE relationship DROP CONSTRAINT fk_200444a056ae248b');
        $this->addSql('ALTER TABLE relationship DROP CONSTRAINT fk_200444a0441b8b65');
        $this->addSql('ALTER TABLE relationship ADD CONSTRAINT FK_200444A056AE248B FOREIGN KEY (user1_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE relationship ADD CONSTRAINT FK_200444A0441B8B65 FOREIGN KEY (user2_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE utensil DROP CONSTRAINT fk_9f283cbcb03a8386');
        $this->addSql('ALTER TABLE utensil ALTER created_by_id DROP NOT NULL');
        $this->addSql('ALTER TABLE utensil ADD CONSTRAINT FK_9F283CBCB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bmi DROP CONSTRAINT FK_502F0A4AA76ED395');
        $this->addSql('ALTER TABLE bmi ADD CONSTRAINT fk_502f0a4aa76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1B03A8386');
        $this->addSql('ALTER TABLE category ALTER created_by_id SET NOT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT fk_64c19c1b03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE measurement DROP CONSTRAINT FK_2CE0D811A76ED395');
        $this->addSql('ALTER TABLE measurement ADD CONSTRAINT fk_2ce0d811a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FF675F31B');
        $this->addSql('ALTER TABLE message ALTER author_id SET NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT fk_b6bd307ff675f31b FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE recipe DROP CONSTRAINT FK_DA88B137F675F31B');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT fk_da88b137f675f31b FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ref_recipe_ingredient DROP CONSTRAINT FK_F6BC88A059D8A214');
        $this->addSql('ALTER TABLE ref_recipe_ingredient ADD CONSTRAINT fk_f6bc88a059d8a214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE relationship DROP CONSTRAINT FK_200444A056AE248B');
        $this->addSql('ALTER TABLE relationship DROP CONSTRAINT FK_200444A0441B8B65');
        $this->addSql('ALTER TABLE relationship ADD CONSTRAINT fk_200444a056ae248b FOREIGN KEY (user1_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE relationship ADD CONSTRAINT fk_200444a0441b8b65 FOREIGN KEY (user2_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE utensil DROP CONSTRAINT FK_9F283CBCB03A8386');
        $this->addSql('ALTER TABLE utensil ALTER created_by_id SET NOT NULL');
        $this->addSql('ALTER TABLE utensil ADD CONSTRAINT fk_9f283cbcb03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
