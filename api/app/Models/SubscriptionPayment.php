<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * SubscriptionPayment — one M-Pesa STK attempt per row. Mirrors Shira's
 * SubscriptionPayment: pending on initiation, completed/failed on callback.
 *
 * NOTE on tenant scoping: the BelongsToTenant scope only applies while a
 * Spatie current tenant is set, which it is NOT on the public callback
 * endpoint — so callback matching by checkout_request_id is global, exactly
 * like Shira. Tenant context during initiation auto-attaches tenant_id.
 */
class SubscriptionPayment extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const PAYMENT_MPESA = 'mpesa';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'amount',
        'currency',
        'phone',
        'payment_method',
        'status',
        'checkout_request_id',
        'receipt_number',
        'transaction_id',
        'paid_at',
        'result_json',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'result_json' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'receipt_number'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
