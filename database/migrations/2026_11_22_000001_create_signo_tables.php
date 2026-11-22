<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('industry', 20)->default('other');
            $table->string('plan', 20)->nullable();          // qr | screen | multi
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('subscription_status', 30)->nullable(); // active | past_due | canceled
            $table->timestamp('subscription_ends_at')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->string('lock_reason')->nullable();
            $table->string('signup_ip', 45)->nullable();
            $table->unsignedTinyInteger('reminder_stage')->default(0);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('template', 20)->default('list');
            $table->string('theme', 10)->default('light');
            $table->string('accent', 20)->default('coral');
            $table->string('font', 20)->default('modern');
            $table->json('data')->nullable();
            $table->string('slug', 16)->unique();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('token', 64)->unique();
            $table->string('pair_code', 8)->nullable()->unique();
            $table->string('device_id', 120)->nullable()->index();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('resolution', 20)->nullable();
            $table->string('client', 20)->nullable();         // web | android
            $table->unsignedInteger('app_version')->nullable();
            $table->string('ip', 45)->nullable();
            $table->boolean('show_all')->default(true);
            $table->unsignedSmallInteger('seconds')->default(12);
            $table->boolean('always_on')->default(true);
            $table->string('on_time', 5)->nullable();         // 08:00
            $table->string('off_time', 5)->nullable();        // 20:00
            $table->json('days')->nullable();                 // [1..7]
            $table->string('screenshot_path')->nullable();
            $table->timestamp('screenshot_at')->nullable();
            $table->timestamp('offline_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('page_screen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['page_id', 'screen_id']);
        });

        Schema::create('screen_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->string('command', 40);
            $table->timestamp('acked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_commands');
        Schema::dropIfExists('page_screen');
        Schema::dropIfExists('screens');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('organizations');
    }
};
