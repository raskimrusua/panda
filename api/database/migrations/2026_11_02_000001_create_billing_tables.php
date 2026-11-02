<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Billing tables — subscription plans (shared catalogue), per-tenant
     * subscriptions, and subscription payment records.
     *
     * Ported from Shira's apps/billing models (farmcore). Plans are reference
     * data and seeded here so a fresh DB + `migrate` is immediately usable by
     * the PWA billing screen; the payment path stays inert until MPESA_* env
     * vars are set (config/daraja.php).
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price_kes')->default(0);
            $table->string('billing_cycle')->default('monthly'); // monthly | yearly
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('status')->default('active'); // trialing | active | past_due | cancelled | expired
            $table->string('billing_cycle')->default('monthly'); // monthly | yearly
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignUlid('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency')->default('KES');
            $table->string('phone')->nullable();
            $table->string('payment_method')->default('mpesa');
            $table->string('status')->default('pending'); // pending | completed | failed
            $table->string('checkout_request_id')->nullable()->unique();
            $table->string('receipt_number')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('result_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Reference data — the three public pricing tiers. Cooperative is a
        // sales-led "Custom" tier and deliberately has no row.
        $plans = [
            ['name' => 'Mkulima', 'slug' => 'mkulima', 'price_kes' => 0, 'sort_order' => 1, 'features' => ['1 farm, 1 user', '2 active seasons', 'disease scan 5/mo']],
            ['name' => 'Shamba', 'slug' => 'shamba', 'price_kes' => 200, 'sort_order' => 2, 'features' => ['2 users', 'unlimited seasons', 'disease scan 30/mo', 'PDF + CSV reports', 'SMS alerts 50/mo']],
            ['name' => 'Boma', 'slug' => 'boma', 'price_kes' => 600, 'sort_order' => 3, 'features' => ['5 users + roles', 'lender-ready reports', 'SMS alerts 200/mo', 'per-plot P&L']],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->insert([
                'id' => Str::ulid()->toString(),
                'name' => $plan['name'],
                'slug' => $plan['slug'],
                'price_kes' => $plan['price_kes'],
                'billing_cycle' => 'monthly',
                'features' => json_encode($plan['features']),
                'is_active' => true,
                'sort_order' => $plan['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
