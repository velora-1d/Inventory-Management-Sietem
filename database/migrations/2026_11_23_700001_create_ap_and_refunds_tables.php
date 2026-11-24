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
        if (! Schema::hasTable('accounts_payable')) {
            Schema::create('accounts_payable', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers');
                $table->unsignedBigInteger('supplier_invoice_id')->nullable()->index();
                $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
                $table->string('invoice_number');
                $table->decimal('total_amount', 15, 2);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('balance_due', 15, 2);
                $table->date('due_date')->nullable();
                $table->string('status', 30)->default('unpaid');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('accounts_payable_payments')) {
            Schema::create('accounts_payable_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accounts_payable_id')->constrained('accounts_payable')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 40);
                $table->date('payment_date');
                $table->string('reference')->nullable();
                $table->unsignedBigInteger('finance_transaction_id')->nullable()->index();
                $table->foreignId('paid_by')->constrained('users');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->string('refund_number')->unique();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('sale_id')->nullable()->index();
                $table->decimal('amount', 15, 2);
                $table->string('method', 40);
                $table->string('status', 30)->default('requested');
                $table->text('reason');
                $table->string('reference')->nullable();
                $table->foreignId('requested_by')->constrained('users');
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('accounts_payable_payments');
        Schema::dropIfExists('accounts_payable');
    }
};
