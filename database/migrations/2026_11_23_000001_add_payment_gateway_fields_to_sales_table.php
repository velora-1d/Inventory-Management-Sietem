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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'payment_gateway')) {
                $table->string('payment_gateway', 50)->nullable()->after('payment_method')->index();
            }
            if (!Schema::hasColumn('sales', 'payment_reference')) {
                $table->string('payment_reference', 150)->nullable()->after('payment_gateway')->index();
            }
            if (!Schema::hasColumn('sales', 'payment_checkout_url')) {
                $table->text('payment_checkout_url')->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('sales', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('payment_checkout_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('sales', 'payment_gateway') ? 'payment_gateway' : null,
                Schema::hasColumn('sales', 'payment_reference') ? 'payment_reference' : null,
                Schema::hasColumn('sales', 'payment_checkout_url') ? 'payment_checkout_url' : null,
                Schema::hasColumn('sales', 'payment_meta') ? 'payment_meta' : null,
            ]));

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
