<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_user_id')->unique();
            $table->string('name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('instructions', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('web')->index();
            $table->string('external_message_id')->nullable()->index();
            $table->string('telegram_user_id')->nullable()->index();
            $table->text('raw_text');
            $table->json('parsed_payload')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_user_id')->index();
            $table->string('product');
            $table->string('category')->index();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->date('transaction_date')->index();
            $table->text('raw_text');
            $table->foreignId('instruction_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('instructions');
        Schema::dropIfExists('customers');
    }
};
