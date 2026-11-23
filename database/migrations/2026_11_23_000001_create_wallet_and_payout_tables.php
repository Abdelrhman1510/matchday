<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('old')->nullable();
            $table->json('new')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['key', 'created_at']);
        });

        Schema::create('commission_overrides', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['plan', 'cafe']);
            $table->unsignedBigInteger('scope_id');
            $table->string('type', 30);
            $table->decimal('value', 10, 2);
            $table->decimal('min', 10, 2)->default(0);
            $table->decimal('max', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['scope', 'scope_id']);
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('SAR');
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('account_holder_name');
            $table->text('iban');
            $table->string('iban_last4', 4);
            $table->string('bank_code', 2);
            $table->string('bank_name');
            $table->string('city');
            $table->string('mobile', 10);
            $table->string('status', 30)->default('pending_verification')->index();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('status', 20)->default('pending')->index();
            // True while this request's debits are on the ledger; guards the reversal.
            $table->boolean('funds_reserved')->default(true);
            $table->string('idempotency_key', 100);
            $table->text('bank_snapshot');
            $table->uuid('payout_uuid')->nullable()->unique();
            $table->string('moyasar_payout_id')->nullable()->index();
            $table->string('moyasar_status', 20)->nullable();
            $table->string('moyasar_channel', 20)->nullable();
            $table->string('moyasar_sequence_number', 30)->nullable();
            $table->text('moyasar_message')->nullable();
            $table->timestamp('moyasar_checked_at')->nullable();
            // Set on used_given_id: never auto-resend, a person must resolve it.
            $table->boolean('payout_blocked')->default(false);
            $table->text('failure_reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('manual_transfer_ref')->nullable();
            $table->date('manual_transfer_date')->nullable();
            $table->string('manual_receipt_path')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['wallet_id', 'idempotency_key']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20);
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('withdrawal_id')->nullable()->constrained('withdrawal_requests')->nullOnDelete();
            $table->string('title');
            $table->string('title_ar');
            $table->string('description')->nullable();
            $table->string('description_ar')->nullable();
            $table->string('reference', 40)->nullable();
            $table->timestamp('available_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['wallet_id', 'status']);
            $table->index(['wallet_id', 'created_at']);
            // A booking can only ever earn, pay commission or be refunded once.
            $table->unique(['booking_id', 'type']);
        });

        Schema::create('withdrawal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_id')->constrained('withdrawal_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('moyasar_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type', 50)->index();
            $table->boolean('live')->default(false);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moyasar_webhook_events');
        Schema::dropIfExists('withdrawal_events');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('commission_overrides');
        Schema::dropIfExists('settings_audit_log');
    }
};
