<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('duchies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('kingdom_id')->constrained()->onDelete('cascade');
            $table->foreignId('duke_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('biome')->default('temperate');
            $table->decimal('tax_rate', 5, 2)->default(10.00);
            $table->integer('coordinates_x')->nullable();
            $table->integer('coordinates_y')->nullable();
            $table->timestamps();
        });

        // Add duchy_id to baronies table
        Schema::table('baronies', function (Blueprint $table) {
            $table->foreignId('duchy_id')->nullable()->after('kingdom_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baronies', function (Blueprint $table) {
            $table->dropForeign(['duchy_id']);
            $table->dropColumn('duchy_id');
        });

        Schema::dropIfExists('duchies');
    }
};
