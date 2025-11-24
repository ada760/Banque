<?php

namespace App\Models\OmPay;

use MongoDB\Laravel\Eloquent\Model;

class Merchant extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'merchants';

    protected $fillable = [
        'name',
        'phone',
        'qr_code', // Données du QR code
        'location',
        'category',
        'status', // active, inactive
        'solde', // Solde du marchand
        'metadata', // Objet JSON pour données supplémentaires
    ];

    protected $casts = [
        'metadata' => 'array',
        'qr_code' => 'array',
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

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}