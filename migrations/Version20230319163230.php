<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230319163230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Inclusão do campo da capa da imagem (cover_image_path).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /** O código comentado abaixo foi gerado para o Sqlite; 
         *  muita coisa é desnecessária. De novo: sempre analise
         *  e modifique o arquivo de migrations quando necessário.
         * */
        // $this->addSql('CREATE TEMPORARY TABLE __temp__episode AS SELECT id, season_id, number, watched FROM episode');
        // $this->addSql('DROP TABLE episode');
        // $this->addSql('CREATE TABLE episode (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, season_id INTEGER NOT NULL, number SMALLINT NOT NULL, watched BOOLEAN NOT NULL, CONSTRAINT FK_DDAA1CDA4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        // $this->addSql('INSERT INTO episode (id, season_id, number, watched) SELECT id, season_id, number, watched FROM __temp__episode');
        // $this->addSql('DROP TABLE __temp__episode');
        // $this->addSql('CREATE INDEX IDX_DDAA1CDA4EC001D1 ON episode (season_id)');
        // $this->addSql('CREATE TEMPORARY TABLE __temp__season AS SELECT id, series_id, number FROM season');
        // $this->addSql('DROP TABLE season');
        // $this->addSql('CREATE TABLE season (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, series_id INTEGER NOT NULL, number SMALLINT NOT NULL, CONSTRAINT FK_F0E45BA95278319C FOREIGN KEY (series_id) REFERENCES series (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        // $this->addSql('INSERT INTO season (id, series_id, number) SELECT id, series_id, number FROM __temp__season');
        // $this->addSql('DROP TABLE __temp__season');
        // $this->addSql('CREATE INDEX IDX_F0E45BA95278319C ON season (series_id)');

        /**
         * Este arquivo foi gerado pelo Symfony (comando php bin\console make:migration)
         */
        $this->addSql('ALTER TABLE series ADD COLUMN cover_image_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // Código gerado pelo Symfony para o Sqlite foi removido.

        // Novo código proposto na aula:
        $this->addSql('ALTER TABLE series DROP COLUMN cover_image_path');
    }
}
