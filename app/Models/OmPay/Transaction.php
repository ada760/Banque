<?php

namespace App\Models\OmPay;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'transactions';

    protected $fillable = [
        'user_id', // Référence vers User PostgreSQL
        'recipient_phone',
        'type', // transfer, payment, recharge
        'amount',
        'status', // pending, success, failed
        'reference', // Pour factures
        'merchant_id', // ObjectId MongoDB
        'service_id', // ObjectId MongoDB
        'qr_metadata', // Objet JSON pour métadonnées QR
        'fee',
        'description',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'qr_metadata' => 'array',
        'transaction_date' => 'datetime',
    ];

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}