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
        Schema::create('kas_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('period_month')->comment('Bulan periode 1-12');
            $table->integer('period_year')->comment('Tahun periode');
            $table->integer('amount')->default(0)->comment('Nominal kas');
            $table->integer('penalty')->default(0)->comment('Total denda');
            $table->integer('total_amount')->default(0)->comment('amount + penalty');
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->string('payment_method')->nullable()->comment('midtrans, manual, cash');
            $table->string('midtrans_order_id')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: satu user hanya bisa punya satu payment per periode
            $table->unique(['user_id', 'period_month', 'period_year'], 'unique_user_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_payments');
    }
};
