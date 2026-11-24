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
        if (! Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->text('address')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_balances')) {
            Schema::create('stock_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->foreignId('product_id')->constrained('products');
                $table->integer('on_hand')->default(0);
                $table->integer('reserved')->default(0);
                $table->integer('incoming')->default(0);
                $table->timestamps();

                $table->unique(['warehouse_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->foreignId('product_id')->constrained('products');
                $table->string('type', 30);
                $table->integer('qty');
                $table->integer('balance_before');
                $table->integer('balance_after');
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['product_id', 'created_at']);
                $table->index(['reference_type', 'reference_id']);
            });
        }

        if (! Schema::hasTable('stock_reservations')) {
            Schema::create('stock_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->foreignId('product_id')->constrained('products');
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->integer('quantity');
                $table->string('status', 30)->default('active');
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_opnames')) {
            Schema::create('stock_opnames', function (Blueprint $table) {
                $table->id();
                $table->string('opname_number', 100)->unique();
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->string('status', 30)->default('draft');
                $table->text('reason')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_opname_items')) {
            Schema::create('stock_opname_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products');
                $table->integer('system_qty');
                $table->integer('counted_qty');
                $table->integer('difference_qty');
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        $now = now();

        $warehouseId = DB::table('warehouses')->where('code', 'MAIN')->value('id');
        if (! $warehouseId) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'code' => 'MAIN',
                'name' => 'Main Warehouse',
                'address' => null,
                'is_default' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('products')) {
            DB::table('products')
                ->select(['id', 'quantity'])
                ->orderBy('id')
                ->chunk(200, function ($products) use ($warehouseId, $now): void {
                    foreach ($products as $product) {
                        $exists = DB::table('stock_balances')
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_id', $product->id)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $onHand = max(0, (int) $product->quantity);

                        DB::table('stock_balances')->insert([
                            'warehouse_id' => $warehouseId,
                            'product_id' => $product->id,
                            'on_hand' => $onHand,
                            'reserved' => 0,
                            'incoming' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        if ($onHand > 0) {
                            DB::table('stock_movements')->insert([
                                'warehouse_id' => $warehouseId,
                                'product_id' => $product->id,
                                'type' => 'IN',
                                'qty' => $onHand,
                                'balance_before' => 0,
                                'balance_after' => $onHand,
                                'reference_type' => 'backfill',
                                'reference_id' => null,
                                'reason' => 'Initial stock backfill',
                                'created_by' => null,
                                'created_at' => $now,
                            ]);
                        }
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('warehouses');
    }
};
