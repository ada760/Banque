<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compte extends Model
{
    use HasFactory, HasUuids;

      protected $fillable = [
        'id',
        'num_compte',
        'devise',
        'status',
        'client_id',
        'type',
        'solde'
    ];

   

    public function client():BelongsTo{
        return $this->belongsTo(Client::class);
    }

    protected $appends = [];

    /**
     * Les attributs qui doivent être castés
     */
    protected $casts = [
        'solde' => 'decimal:2',
    ];

}
