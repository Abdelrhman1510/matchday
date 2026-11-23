<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = ['withdrawals.view', 'withdrawals.approve', 'finance.reveal_iban'];

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Null on bookings made before this flow existed; those keep their old rules.
            $table->string('payment_status', 20)->nullable()->after('status')->index();
            $table->string('payment_method', 20)->nullable()->after('payment_status');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_method')->index();
            $table->timestamp('paid_at')->nullable()->after('payment_expires_at');
            $table->string('cancel_reason', 40)->nullable()->after('cancelled_at');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('gateway_fee', 10, 2)->default(0)->after('commission_amount');
            $table->decimal('cafe_net_amount', 10, 2)->nullable()->after('gateway_fee');
            $table->json('fees_snapshot')->nullable()->after('cafe_net_amount');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            $table->boolean('is_most_popular')->nullable()->after('sort_order');
        });

        // Bookings already charged online count as paid so check-in keeps working.
        $paidIds = DB::table('payments')
            ->whereNotNull('booking_id')
            ->whereIn('status', ['paid', 'completed'])
            ->pluck('booking_id');
        foreach ($paidIds->chunk(500) as $chunk) {
            DB::table('bookings')->whereIn('id', $chunk->all())->update([
                'payment_status' => 'paid',
                'payment_method' => 'online',
            ]);
        }

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }
        Role::findOrCreate('platform_admin', 'web')->givePermissionTo(self::PERMISSIONS);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'is_most_popular']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['payment_expires_at']);
            $table->dropColumn([
                'payment_status', 'payment_method', 'payment_expires_at', 'paid_at', 'cancel_reason',
                'commission_amount', 'gateway_fee', 'cafe_net_amount', 'fees_snapshot',
            ]);
        });
    }
};
