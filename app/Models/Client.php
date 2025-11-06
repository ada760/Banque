<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory, HasUuids; 

    protected $fillable = [
        'id',         
        'telephone',   
        'cni',       
        'adresse', 
        'user_id'   
    ];

    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function compte():HasMany{
        return $this->hasMany(Compte::class);
    }





    // protected $keyType = 'string'; les deux equivalent de HasUuids
    // public $incrementing = false;


}
