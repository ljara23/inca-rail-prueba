<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name');
            $table->string('slug')->unique();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('name');
            $table->string('slug')->unique();
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->string('area_name')->nullable();
            $table->string('emblem')->nullable();
            $table->string('season')->nullable();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('tla', 10)->nullable();
            $table->string('crest')->nullable();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->unsignedBigInteger('external_id')->unique();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->string('name');
            $table->string('position')->nullable();
            $table->unsignedSmallInteger('shirt_number')->nullable();
        });

        Schema::create('competition_team', function (Blueprint $table) {
            $table->foreignId('competition_id')
                ->constrained()->cascadeOnDelete();

            $table->foreignId('team_id')
                ->constrained()->cascadeOnDelete();

            $table->primary(['competition_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_team');

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'external_id', 'team_id', 'name',
                'position', 'shirt_number',
            ]);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'external_id', 'name', 'short_name', 'tla', 'crest',
            ]);
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
                'external_id', 'name', 'code',
                'area_name', 'emblem', 'season',
            ]);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['name', 'slug']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['name', 'slug']);
        });
    }
};
