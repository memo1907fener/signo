<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->json('brand')->nullable();                 // {logo, accent, colors[], style, show_logo}
            $table->json('notice')->nullable();                // Sofortmeldung {text, until, full}
            $table->string('public_token', 24)->nullable()->unique();   // Feedback- und Stempelkarten-Link
            $table->boolean('feedback_enabled')->default(false);
            $table->string('google_review_url', 500)->nullable();
            $table->json('loyalty')->nullable();               // {enabled, title, goal, reward, pin}
            $table->boolean('offline_alerts')->default(true);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable();
        });

        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 1000)->nullable();
            $table->string('contact', 120)->nullable();
            $table->string('lang', 5)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('stamp_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->unsignedSmallInteger('stamps')->default(0);
            $table->unsignedInteger('total_stamps')->default(0);
            $table->unsignedSmallInteger('redeemed')->default(0);
            $table->timestamp('last_stamp_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'updated_at']);
        });

        // Statistik: Aufrufe der Handy-Seiten je Tag
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('views')->default(0);
            $table->unique(['page_id', 'day']);
            $table->index(['organization_id', 'day']);
        });

        // Statistik: Laufzeit der Bildschirme je Tag (Sekunden)
        Schema::create('screen_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('seconds')->default(0);
            $table->unique(['screen_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_days');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('stamp_cards');
        Schema::dropIfExists('feedbacks');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('locale'));
        Schema::table('organizations', fn (Blueprint $t) => $t->dropColumn(['brand', 'notice', 'public_token', 'feedback_enabled', 'google_review_url', 'loyalty', 'offline_alerts']));
    }
};
