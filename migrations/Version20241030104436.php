<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241030104436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alquiler CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE alquiler ADD CONSTRAINT FK_655BED39BF396750 FOREIGN KEY (id) REFERENCES solicitud (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE compra CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE compra ADD CONSTRAINT FK_9EC131FFBF396750 FOREIGN KEY (id) REFERENCES solicitud (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE repacion CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE repacion ADD CONSTRAINT FK_DB53EA8FBF396750 FOREIGN KEY (id) REFERENCES solicitud (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solicitud ADD discr VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alquiler DROP FOREIGN KEY FK_655BED39BF396750');
        $this->addSql('ALTER TABLE alquiler CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE compra DROP FOREIGN KEY FK_9EC131FFBF396750');
        $this->addSql('ALTER TABLE compra CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE solicitud DROP discr');
        $this->addSql('ALTER TABLE repacion DROP FOREIGN KEY FK_DB53EA8FBF396750');
        $this->addSql('ALTER TABLE repacion CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}
