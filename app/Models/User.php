<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'titulaire',
        'email',
        'phone_number',
        'secret_code',
        'password',
        'last_otp_request',
        'otp_attempts',
        'blocked_until',
    ];
   

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'secret_code' => 'hashed',
        'blocked_until' => 'datetime',
        'last_otp_request' => 'datetime',
    ];

    

     public function client():HasOne{
        return $this->hasOne(Client::class);
    }

    public function admin():HasOne{
        return $this->hasOne(Admin::class);
    }

    public function getRoleAttribute()
    {
        if($this->client)
        {
            return 'client';
            
        }else if($this->admin)
        {
            return 'admin';
        }
    }



}
