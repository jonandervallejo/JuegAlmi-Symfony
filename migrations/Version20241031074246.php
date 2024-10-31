<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241031074246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alquiler (id INT NOT NULL, fecha_inicio DATE NOT NULL, fecha_fin DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE compra (id INT NOT NULL, adquisiciones VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE producto (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) DEFAULT NULL, descripcion VARCHAR(255) DEFAULT NULL, stock INT NOT NULL, imagen VARCHAR(255) NOT NULL, precio DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE producto_solicitado (id INT AUTO_INCREMENT NOT NULL, id_producto_id INT DEFAULT NULL, id_solicitud_id INT DEFAULT NULL, INDEX IDX_549FD0476E57A479 (id_producto_id), INDEX IDX_549FD0473F78A396 (id_solicitud_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE repacion (id INT NOT NULL, fecha_inicio DATE NOT NULL, fecha_fin DATE NOT NULL, incidencia VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud (id INT AUTO_INCREMENT NOT NULL, precio_solicitud DOUBLE PRECISION DEFAULT NULL, discr VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ubicacion (id INT AUTO_INCREMENT NOT NULL, id_usuario_id INT DEFAULT NULL, latitud VARCHAR(255) DEFAULT NULL, longitud VARCHAR(255) DEFAULT NULL, INDEX IDX_DC158CB87EB2C349 (id_usuario_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE usuario (id INT AUTO_INCREMENT NOT NULL, id_solicitud_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', nombre VARCHAR(255) DEFAULT NULL, apellido1 VARCHAR(255) DEFAULT NULL, apellido2 VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, rol VARCHAR(255) DEFAULT NULL, foto_perfil VARCHAR(255) DEFAULT NULL, INDEX IDX_2265B05D3F78A396 (id_solicitud_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alquiler ADD CONSTRAINT FK_655BED39BF396750 FOREIGN KEY (id) REFERENCES solicitud (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE compra ADD CONSTRAINT FK_9EC131FFBF396750 FOREIGN KEY (id) REFERENCES solicitud (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE producto_solicitado ADD CONSTRAINT FK_549FD0476E57A479 FOREIGN KEY (id_producto_id) REFERENCES producto (id)');
        $this->addSql('ALTER TABLE producto_solicitado ADD CONSTRAINT FK_549FD0473F78A396 FOREIGN KEY (id_solicitud_id) REFERENCES solicitud (id)');
        $this->addSql('ALTER TABLE repacion ADD CONSTRAINT FK_DB53EA8FBF396750 FOREIGN KEY (id) REFERENCES solicitud (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ubicacion ADD CONSTRAINT FK_DC158CB87EB2C349 FOREIGN KEY (id_usuario_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE usuario ADD CONSTRAINT FK_2265B05D3F78A396 FOREIGN KEY (id_solicitud_id) REFERENCES solicitud (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alquiler DROP FOREIGN KEY FK_655BED39BF396750');
        $this->addSql('ALTER TABLE compra DROP FOREIGN KEY FK_9EC131FFBF396750');
        $this->addSql('ALTER TABLE producto_solicitado DROP FOREIGN KEY FK_549FD0476E57A479');
        $this->addSql('ALTER TABLE producto_solicitado DROP FOREIGN KEY FK_549FD0473F78A396');
        $this->addSql('ALTER TABLE repacion DROP FOREIGN KEY FK_DB53EA8FBF396750');
        $this->addSql('ALTER TABLE ubicacion DROP FOREIGN KEY FK_DC158CB87EB2C349');
        $this->addSql('ALTER TABLE usuario DROP FOREIGN KEY FK_2265B05D3F78A396');
        $this->addSql('DROP TABLE alquiler');
        $this->addSql('DROP TABLE compra');
        $this->addSql('DROP TABLE producto');
        $this->addSql('DROP TABLE producto_solicitado');
        $this->addSql('DROP TABLE repacion');
        $this->addSql('DROP TABLE solicitud');
        $this->addSql('DROP TABLE ubicacion');
        $this->addSql('DROP TABLE usuario');
    }
}
