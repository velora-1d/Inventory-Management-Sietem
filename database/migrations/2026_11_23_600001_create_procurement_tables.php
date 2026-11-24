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
        if (! Schema::hasTable('purchase_requests')) {
            Schema::create('purchase_requests', function (Blueprint $table) {
                $table->id();
                $table->string('pr_number', 100)->unique();
                $table->string('status', 30)->default('pending_approval');
                $table->text('notes')->nullable();
                $table->foreignId('requested_by')->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_request_items')) {
            Schema::create('purchase_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products');
                $table->integer('quantity');
                $table->decimal('estimated_unit_price', 15, 2)->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_request_approvals')) {
            Schema::create('purchase_request_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
                $table->foreignId('actor_id')->constrained('users');
                $table->string('decision', 20);
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('po_number', 100)->unique();
                $table->foreignId('supplier_id')->constrained('suppliers');
                $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
                $table->string('status', 40)->default('draft');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_total', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->date('expected_date')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->integer('revision_number')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products');
                $table->integer('quantity_ordered');
                $table->integer('quantity_received')->default(0);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('subtotal', 15, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_order_approvals')) {
            Schema::create('purchase_order_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->foreignId('actor_id')->constrained('users');
                $table->string('decision', 20);
                $table->integer('revision_number')->default(0);
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('grn_number', 100)->unique();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders');
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->foreignId('received_by')->constrained('users');
                $table->timestamp('received_at')->useCurrent();
                $table->string('qc_status', 30)->default('passed');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
                $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items');
                $table->foreignId('product_id')->constrained('products');
                $table->integer('quantity_received');
                $table->integer('quantity_accepted');
                $table->integer('quantity_rejected')->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('supplier_invoices')) {
            Schema::create('supplier_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number');
                $table->foreignId('supplier_id')->constrained('suppliers');
                $table->foreignId('purchase_order_id')->constrained('purchase_orders');
                $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->decimal('amount', 15, 2);
                $table->string('match_status', 30)->default('pending');
                $table->text('match_notes')->nullable();
                $table->string('status', 30)->default('draft');
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_returns')) {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->string('return_number', 100)->unique();
                $table->foreignId('goods_receipt_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders');
                $table->foreignId('supplier_id')->constrained('suppliers');
                $table->text('reason');
                $table->string('status', 30)->default('completed');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_return_items')) {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products');
                $table->integer('quantity');
                $table->decimal('unit_price', 15, 2);
                $table->decimal('subtotal', 15, 2);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_approvals');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_request_approvals');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};
