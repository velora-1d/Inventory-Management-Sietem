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
        if (! Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->string('carrier');
                $table->string('service')->nullable();
                $table->string('tracking_number')->nullable()->index();
                $table->string('status', 30)->default('ready');
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('estimated_delivery_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->string('proof_of_delivery_path')->nullable();
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('confirmation_source', 20)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shipment_events')) {
            Schema::create('shipment_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
                $table->string('status', 40);
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('order_tracking_events')) {
            Schema::create('order_tracking_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->string('milestone_code', 50);
                $table->string('title');
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['order_id', 'is_public']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_tracking_events');
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
    }
};
