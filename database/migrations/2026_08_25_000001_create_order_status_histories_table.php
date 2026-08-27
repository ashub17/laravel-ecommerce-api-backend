<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Which field moved: 'status' or 'payment_status'.
            $table->string('type');
            $table->string('from')->nullable();
            $table->string('to');
            $table->string('note')->nullable();

            // The admin or customer who caused the change; null when the
            // system did it, such as the initial status on checkout.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index(['order_id', 'type']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Stock is returned to the catalog exactly once per order. This
            // stamp is the guard against a repeated cancel or refund
            // restocking the same items twice.
            $table->timestamp('stock_restored_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_restored_at');
        });

        Schema::dropIfExists('order_status_histories');
    }
};
