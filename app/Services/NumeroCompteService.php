<?php


namespace App\Services;

class NumeroCompteService
{
    public function generate(): string
    {
        $prefix = 'SN';
        $year = date('Y');
        $random = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        return $prefix . $year . $random;
    }
}