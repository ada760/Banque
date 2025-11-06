<?php


namespace App\Services;

use Illuminate\Support\Facades\Date;

class NumeroCompteService
{
    public function generate(): string
    {
        $prefix = 'C';
       
        $random = rand(0, 2);
        
        return $prefix . $random.strtotime("now");
    }
}