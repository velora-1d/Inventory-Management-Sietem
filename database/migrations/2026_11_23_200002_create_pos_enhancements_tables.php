<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('barcode', 100)->nullable()->unique()->after('sku');
            });
        }

        if (!Schema::hasTable('cash_registers')) {
            Schema::create('cash_registers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('location', 150)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cash_shifts')) {
            Schema::create('cash_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cash_register_id')->constrained('cash_registers')->restrictOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->timestamp('opened_at');
                $table->timestamp('closed_at')->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->decimal('expected_closing_balance', 15, 2)->default(0);
                $table->decimal('actual_closing_balance', 15, 2)->nullable();
                $table->decimal('variance', 15, 2)->default(0);
                $table->string('status', 20)->default('open')->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('sale_payments')) {
            Schema::create('sale_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->string('payment_method', 50)->index();
                $table->decimal('amount', 15, 2);
                $table->string('reference', 150)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('cash_registers')) {
            $exists = DB::table('cash_registers')->where('code', 'REG-01')->exists();
            if (!$exists) {
                $now = now();
                DB::table('cash_registers')->insert([
                    'code' => 'REG-01',
                    'name' => 'Kasir Utama (Register 1)',
                    'location' => 'Front Counter',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('cash_registers');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'barcode')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropUnique(['barcode']);
                });
            } catch (\Throwable) {
                // Index may not exist or already dropped
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('barcode');
            });
        }
    }
};
