<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241112075242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alquiler CHANGE fecha_inicio fecha_inicio DATE DEFAULT NULL, CHANGE fecha_fin fecha_fin DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE ubicacion CHANGE latitud latitud VARCHAR(255) DEFAULT NULL, CHANGE longitud longitud VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alquiler CHANGE fecha_inicio fecha_inicio DATE NOT NULL, CHANGE fecha_fin fecha_fin DATE NOT NULL');
        $this->addSql('ALTER TABLE ubicacion CHANGE latitud latitud DOUBLE PRECISION DEFAULT NULL, CHANGE longitud longitud DOUBLE PRECISION DEFAULT NULL');
    }
}
