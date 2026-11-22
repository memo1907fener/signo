<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('languages')->nullable();        // z. B. ["en","it"]
            $table->json('translations')->nullable();     // {"en": {...}, "it": {...}}
            $table->string('translated_hash', 32)->nullable();
            $table->boolean('tv_languages')->default(false);
            $table->boolean('orderable')->default(false);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('ordering_enabled')->default(false);
            $table->string('ordering_mode', 10)->default('pickup');   // pickup | table
            $table->string('ordering_note')->nullable();
            $table->string('kitchen_token', 40)->nullable()->unique();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('number');
            $table->string('code', 16)->unique();
            $table->string('status', 12)->default('new');           // new | preparing | ready | done | canceled
            $table->string('customer_name', 80)->nullable();
            $table->string('table_no', 20)->nullable();
            $table->string('note', 300)->nullable();
            $table->json('items');
            $table->unsignedInteger('total_cents')->default(0);
            $table->string('lang', 5)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::table('organizations', fn (Blueprint $t) => $t->dropColumn(['ordering_enabled', 'ordering_mode', 'ordering_note', 'kitchen_token']));
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn(['languages', 'translations', 'translated_hash', 'tv_languages', 'orderable']));
    }
};
