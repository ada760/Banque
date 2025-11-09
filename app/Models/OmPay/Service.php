<?php

namespace App\Models\OmPay;

use MongoDB\Laravel\Eloquent\Model;

class Service extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'services';

    protected $fillable = [
        'name', // ex: "SDE", "Data", "Recharge"
        'type', // bill, recharge
        'provider', // ex: "Orange", "SDE"
        'fee_percentage',
        'status', // active, inactive
        'metadata', // Objet JSON pour config spécifique
    ];

    protected $casts = [
        'fee_percentage' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relations
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }
}