<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE applications_new (
                "id" integer primary key autoincrement not null,
                "job_listing_id" integer not null,
                "candidate_id" integer not null,
                "resume_path" varchar,
                "contact_email" varchar,
                "contact_phone" varchar,
                "status" varchar not null default \'pending\',
                "applied_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                "rejection_reason" text,
                "resume_name" varchar,
                foreign key("job_listing_id") references "job_listings"("id") on delete cascade,
                foreign key("candidate_id") references "users"("id") on delete cascade
            )
        ');

        DB::statement('INSERT INTO applications_new SELECT * FROM applications');
        DB::statement('DROP TABLE applications');
        DB::statement('ALTER TABLE applications_new RENAME TO applications');
        DB::statement('CREATE UNIQUE INDEX "applications_job_listing_id_candidate_id_unique" on "applications" ("job_listing_id", "candidate_id")');
    }

    public function down(): void
    {
        DB::statement('
            CREATE TABLE applications_new (
                "id" integer primary key autoincrement not null,
                "job_listing_id" integer not null,
                "candidate_id" integer not null,
                "resume_path" varchar,
                "contact_email" varchar,
                "contact_phone" varchar,
                "status" varchar check ("status" in (\'pending\', \'accepted\', \'rejected\')) not null default \'pending\',
                "applied_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                "rejection_reason" text,
                "resume_name" varchar,
                foreign key("job_listing_id") references "job_listings"("id") on delete cascade,
                foreign key("candidate_id") references "users"("id") on delete cascade
            )
        ');

        DB::statement('INSERT INTO applications_new SELECT * FROM applications');
        DB::statement('DROP TABLE applications');
        DB::statement('ALTER TABLE applications_new RENAME TO applications');
        DB::statement('CREATE UNIQUE INDEX "applications_job_listing_id_candidate_id_unique" on "applications" ("job_listing_id", "candidate_id")');
    }
};
