<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112154719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE houses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE service_requests_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE service_tasks_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "users_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE houses (id INT NOT NULL, owner_id INT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, description TEXT DEFAULT NULL, bedrooms INT NOT NULL, bathrooms INT NOT NULL, square_meters INT NOT NULL, amenities JSON DEFAULT NULL, special_instructions JSON DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_95D7F5CB7E3C61F9 ON houses (owner_id)');
        $this->addSql('COMMENT ON COLUMN houses.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN houses.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE house_owners (house_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(house_id, user_id))');
        $this->addSql('CREATE INDEX IDX_F1CF0B736BB74515 ON house_owners (house_id)');
        $this->addSql('CREATE INDEX IDX_F1CF0B73A76ED395 ON house_owners (user_id)');
        $this->addSql('CREATE TABLE service_requests (id INT NOT NULL, house_id INT NOT NULL, created_by_id INT NOT NULL, assigned_cleaner_id INT DEFAULT NULL, service_type VARCHAR(50) NOT NULL, current_place VARCHAR(50) NOT NULL, scheduled_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, description TEXT DEFAULT NULL, notes TEXT DEFAULT NULL, estimated_duration NUMERIC(10, 2) DEFAULT NULL, actual_duration NUMERIC(10, 2) DEFAULT NULL, priority VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, workflow_history JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_82F38D6C6BB74515 ON service_requests (house_id)');
        $this->addSql('CREATE INDEX IDX_82F38D6CB03A8386 ON service_requests (created_by_id)');
        $this->addSql('CREATE INDEX IDX_82F38D6CD57CCD68 ON service_requests (assigned_cleaner_id)');
        $this->addSql('COMMENT ON COLUMN service_requests.scheduled_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN service_requests.completed_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN service_requests.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN service_requests.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE service_tasks (id INT NOT NULL, service_request_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, sort_order INT NOT NULL, is_required BOOLEAN NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completion_notes TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F92A8870D42F8111 ON service_tasks (service_request_id)');
        $this->addSql('COMMENT ON COLUMN service_tasks.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "users" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON "users" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE houses ADD CONSTRAINT FK_95D7F5CB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "users" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE house_owners ADD CONSTRAINT FK_F1CF0B736BB74515 FOREIGN KEY (house_id) REFERENCES houses (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE house_owners ADD CONSTRAINT FK_F1CF0B73A76ED395 FOREIGN KEY (user_id) REFERENCES "users" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_requests ADD CONSTRAINT FK_82F38D6C6BB74515 FOREIGN KEY (house_id) REFERENCES houses (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_requests ADD CONSTRAINT FK_82F38D6CB03A8386 FOREIGN KEY (created_by_id) REFERENCES "users" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_requests ADD CONSTRAINT FK_82F38D6CD57CCD68 FOREIGN KEY (assigned_cleaner_id) REFERENCES "users" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_tasks ADD CONSTRAINT FK_F92A8870D42F8111 FOREIGN KEY (service_request_id) REFERENCES service_requests (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE houses_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE service_requests_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE service_tasks_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "users_id_seq" CASCADE');
        $this->addSql('ALTER TABLE houses DROP CONSTRAINT FK_95D7F5CB7E3C61F9');
        $this->addSql('ALTER TABLE house_owners DROP CONSTRAINT FK_F1CF0B736BB74515');
        $this->addSql('ALTER TABLE house_owners DROP CONSTRAINT FK_F1CF0B73A76ED395');
        $this->addSql('ALTER TABLE service_requests DROP CONSTRAINT FK_82F38D6C6BB74515');
        $this->addSql('ALTER TABLE service_requests DROP CONSTRAINT FK_82F38D6CB03A8386');
        $this->addSql('ALTER TABLE service_requests DROP CONSTRAINT FK_82F38D6CD57CCD68');
        $this->addSql('ALTER TABLE service_tasks DROP CONSTRAINT FK_F92A8870D42F8111');
        $this->addSql('DROP TABLE houses');
        $this->addSql('DROP TABLE house_owners');
        $this->addSql('DROP TABLE service_requests');
        $this->addSql('DROP TABLE service_tasks');
        $this->addSql('DROP TABLE "users"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
