<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old constraint and add new one with all ranks for religions and cults
        DB::statement('ALTER TABLE religion_members DROP CONSTRAINT IF EXISTS religion_members_rank_check');
        DB::statement("ALTER TABLE religion_members ADD CONSTRAINT religion_members_rank_check CHECK (rank IN ('prophet', 'archbishop', 'priest', 'deacon', 'apostle', 'acolyte', 'disciple', 'follower'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE religion_members DROP CONSTRAINT IF EXISTS religion_members_rank_check');
        DB::statement("ALTER TABLE religion_members ADD CONSTRAINT religion_members_rank_check CHECK (rank IN ('prophet', 'priest', 'follower'))");
    }
};
