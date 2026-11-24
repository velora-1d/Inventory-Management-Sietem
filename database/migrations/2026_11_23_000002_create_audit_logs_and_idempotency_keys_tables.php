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
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100)->index();
                $table->string('entity_type', 150)->nullable();
                $table->string('entity_id', 64)->nullable();
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index(['entity_type', 'entity_id']);
            });
        }

        if (!Schema::hasTable('idempotency_keys')) {
            Schema::create('idempotency_keys', function (Blueprint $table) {
                $table->id();
                $table->string('key', 128)->unique();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('route', 150);
                $table->string('request_hash', 64);
                $table->smallInteger('status_code')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('audit_logs');
    }
};
