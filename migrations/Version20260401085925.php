<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401085925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add extended profile fields to professionals + create reviews table';
    }

    public function up(Schema $schema): void
    {
        // Add new columns to professionals
        $this->addSql('ALTER TABLE professionals ADD COLUMN location VARCHAR(200) DEFAULT NULL');
        $this->addSql('ALTER TABLE professionals ADD COLUMN verified BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE professionals ADD COLUMN years_of_experience INT DEFAULT NULL');
        $this->addSql('ALTER TABLE professionals ADD COLUMN video_url VARCHAR(500) DEFAULT NULL');
        $this->addSql("ALTER TABLE professionals ADD COLUMN degrees JSON NOT NULL DEFAULT '[]'");
        $this->addSql("ALTER TABLE professionals ADD COLUMN areas_of_expertise JSON NOT NULL DEFAULT '[]'");
        $this->addSql('ALTER TABLE professionals ADD COLUMN who_i_work_with TEXT DEFAULT NULL');
        $this->addSql("ALTER TABLE professionals ADD COLUMN specialities JSON NOT NULL DEFAULT '[]'");

        // Create reviews table
        $this->addSql('CREATE TABLE reviews (
            id SERIAL NOT NULL,
            professional_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL,
            comment TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_6970EB0FDC0BD19F ON reviews (professional_id)');
        $this->addSql('CREATE INDEX IDX_6970EB0FA76ED395 ON reviews (user_id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_reviews_professional FOREIGN KEY (professional_id) REFERENCES professionals (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql("COMMENT ON COLUMN reviews.created_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reviews');
        $this->addSql('ALTER TABLE professionals DROP COLUMN location');
        $this->addSql('ALTER TABLE professionals DROP COLUMN verified');
        $this->addSql('ALTER TABLE professionals DROP COLUMN years_of_experience');
        $this->addSql('ALTER TABLE professionals DROP COLUMN video_url');
        $this->addSql('ALTER TABLE professionals DROP COLUMN degrees');
        $this->addSql('ALTER TABLE professionals DROP COLUMN areas_of_expertise');
        $this->addSql('ALTER TABLE professionals DROP COLUMN who_i_work_with');
        $this->addSql('ALTER TABLE professionals DROP COLUMN specialities');
    }
}
