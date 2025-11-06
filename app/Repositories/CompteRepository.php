<?php


namespace App\Repositories;

use App\Models\Compte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompteRepository extends BaseRepository{

    public function __construct(Compte $compte)
    {
        $this->model = $compte;
    }

}
