<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Session table for database session driver
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('ticket_status', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->string('background_color', 20)->default('#6c757d');
            $table->string('filter_category', 20)->default('in_bearbeitung');
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->string('title', 255);
            $table->text('description');
            $table->text('ki_summary')->nullable();
            $table->text('ki_interim')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->string('assignee', 200)->nullable();
            $table->boolean('show_on_website')->default(false);
            $table->text('public_comment')->nullable();
            $table->integer('affected_neighbors')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->tinyInteger('do_not_track')->default(0);
            $table->string('secret_string', 50)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('closed_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('status_id')->references('id')->on('ticket_status');
            $table->index('secret_string');
            $table->index('follow_up_date');
            $table->index('do_not_track');
            $table->index('affected_neighbors');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('username', 50);
            $table->text('content');
            $table->boolean('is_visible')->default(true);
            $table->string('hidden_by', 50)->nullable();
            $table->dateTime('hidden_at')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->index('is_visible');
        });

        Schema::create('comment_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id');
            $table->string('username', 50);
            $table->enum('value', ['up', 'down']);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('comment_id')->references('id')->on('comments');
            $table->unique(['comment_id', 'username'], 'unique_comment_vote');
        });

        Schema::create('ticket_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('username', 50);
            $table->enum('value', ['up', 'down']);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->unique(['ticket_id', 'username'], 'unique_ticket_vote');
        });

        Schema::create('master_links', function (Blueprint $table) {
            $table->id();
            $table->string('link_code', 255)->unique();
            $table->text('description')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->string('partner_name', 50)->nullable();
            $table->string('partner_link', 255)->unique();
            $table->text('partner_list')->nullable();
            $table->boolean('is_master')->default(false);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('ticket_id')->references('id')->on('tickets');
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('file_type', 50);
            $table->integer('file_size');
            $table->string('uploaded_by', 100);
            $table->dateTime('upload_date');
            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
        });

        Schema::create('contact_persons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('contact_notes')->nullable();
            $table->text('responsibility_notes')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ticket_contact_persons', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedInteger('contact_person_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['ticket_id', 'contact_person_id'], 'ticket_contact_unique');
            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->foreign('contact_person_id')->references('id')->on('contact_persons');
        });

        Schema::create('news', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->text('content');
            $table->string('image_filename', 255)->nullable();
            $table->date('event_date');
            $table->timestamp('created_at')->useCurrent();
            $table->string('created_by', 50);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('event_date');
        });

        // Views and MySQL Functions (only for MySQL connection)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                CREATE OR REPLACE VIEW comment_statistics AS
                SELECT
                    c.id as comment_id,
                    c.ticket_id,
                    COUNT(CASE WHEN cv.value = 'up' THEN 1 END) as up_votes,
                    COUNT(CASE WHEN cv.value = 'down' THEN 1 END) as down_votes,
                    COUNT(cv.id) as total_votes
                FROM comments c
                LEFT JOIN comment_votes cv ON c.id = cv.comment_id
                GROUP BY c.id, c.ticket_id
            ");

            DB::statement("
                CREATE OR REPLACE VIEW ticket_statistics AS
                SELECT
                    t.id as ticket_id,
                    COUNT(CASE WHEN tv.value = 'up' THEN 1 END) as up_votes,
                    COUNT(CASE WHEN tv.value = 'down' THEN 1 END) as down_votes,
                    COUNT(tv.id) as total_votes
                FROM tickets t
                LEFT JOIN ticket_votes tv ON t.id = tv.ticket_id
                GROUP BY t.id
            ");
        }

        // MySQL Functions and Triggers (only for MySQL connection)
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared("
                DROP FUNCTION IF EXISTS get_ticket_partners;
                CREATE FUNCTION get_ticket_partners(p_ticket_id INT)
                RETURNS TEXT DETERMINISTIC
                BEGIN
                    DECLARE partner_list TEXT;
                    SELECT GROUP_CONCAT(DISTINCT partner_name ORDER BY created_at SEPARATOR ', ')
                    INTO partner_list FROM partners
                    WHERE ticket_id = p_ticket_id AND partner_name IS NOT NULL;
                    RETURN COALESCE(partner_list, '');
                END
            ");

            DB::unprepared("
                DROP FUNCTION IF EXISTS has_sufficient_positive_votes;
                CREATE FUNCTION has_sufficient_positive_votes(p_ticket_id INT, p_min_votes INT)
                RETURNS BOOLEAN DETERMINISTIC
                BEGIN
                    DECLARE total_positive_votes INT;
                    SELECT COUNT(DISTINCT c.id) INTO total_positive_votes
                    FROM comments c
                    JOIN comment_statistics cs ON c.id = cs.comment_id
                    WHERE c.ticket_id = p_ticket_id AND cs.up_votes > cs.down_votes;
                    RETURN total_positive_votes >= p_min_votes;
                END
            ");

            DB::unprepared("
                DROP FUNCTION IF EXISTS generate_random_string;
                CREATE FUNCTION generate_random_string()
                RETURNS VARCHAR(50) DETERMINISTIC
                BEGIN
                    DECLARE result VARCHAR(50) DEFAULT '';
                    DECLARE chars VARCHAR(62) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    DECLARE i INT DEFAULT 1;
                    WHILE i <= 50 DO
                        SET result = CONCAT(result, SUBSTRING(chars, FLOOR(1 + RAND() * 62), 1));
                        SET i = i + 1;
                    END WHILE;
                    RETURN result;
                END
            ");

            DB::unprepared("
                DROP TRIGGER IF EXISTS tickets_before_insert;
                CREATE TRIGGER tickets_before_insert
                BEFORE INSERT ON tickets
                FOR EACH ROW
                BEGIN
                    IF NEW.secret_string IS NULL THEN
                        SET NEW.secret_string = generate_random_string();
                    END IF;
                END
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS tickets_before_insert');
            DB::unprepared('DROP FUNCTION IF EXISTS generate_random_string');
            DB::unprepared('DROP FUNCTION IF EXISTS has_sufficient_positive_votes');
            DB::unprepared('DROP FUNCTION IF EXISTS get_ticket_partners');
        }
        DB::statement('DROP VIEW IF EXISTS ticket_statistics');
        DB::statement('DROP VIEW IF EXISTS comment_statistics');

        Schema::dropIfExists('ticket_contact_persons');
        Schema::dropIfExists('contact_persons');
        Schema::dropIfExists('news');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('master_links');
        Schema::dropIfExists('ticket_votes');
        Schema::dropIfExists('comment_votes');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_status');
        Schema::dropIfExists('sessions');
    }
};
