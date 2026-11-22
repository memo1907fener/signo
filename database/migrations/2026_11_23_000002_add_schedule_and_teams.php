<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('schedule')->nullable();   // {from, until, days[], start, end}
        });

        // Team & Standorte: ein Benutzer kann mehreren Betrieben angehören
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 12)->default('owner');   // owner | editor
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 12)->default('editor');
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        // Bestehende Zuordnungen übernehmen
        foreach (DB::table('users')->whereNotNull('organization_id')->get(['id', 'organization_id']) as $u) {
            DB::table('organization_user')->insertOrIgnore([
                'organization_id' => $u->organization_id, 'user_id' => $u->id, 'role' => 'owner',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('organization_user');
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn('schedule'));
    }
};
